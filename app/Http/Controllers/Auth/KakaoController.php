<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class KakaoController extends Controller
{
    /**
     * Redirect to Kakao OAuth
     */
    public function redirect(Request $request)
    {
        // mobilescreen 파라미터가 있으면 세션에 저장
        if ($request->has('mobilescreen')) {
            $request->session()->put('login.mobilescreen', $request->input('mobilescreen'));
        }

        return Socialite::driver('kakao')->redirect();
    }

    /**
     * Handle Kakao OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            // 모바일에서 직접 파라미터로 전달된 경우 처리
            if ($request->has('access_token') && $request->has('user_id')) {
                return $this->handleMobileCallback($request);
            }

            // 웹에서 Socialite를 통한 일반 OAuth 처리
            $kakaoUser = Socialite::driver('kakao')->stateless()->user();

            // 카카오 이메일이 없으면 임시 이메일 생성
            $email = $kakaoUser->email ?? $kakaoUser->id.'@kakao.pcaview.com';

            // 프로필 이미지를 S3에 저장
            $profileImageUrl = $kakaoUser->avatar ?? $kakaoUser->avatar_original ?? null;
            $profileImage    = null;
            if ($profileImageUrl) {
                $profileImage = $this->uploadProfileImageToS3($profileImageUrl, $kakaoUser->id);
            }

            // Find user by kakao_id first
            $user = User::where('kakao_id', $kakaoUser->id)->first();

            if (! $user) {
                // Then check if email already exists
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update existing user with Kakao ID and profile photo
                    $user->update([
                        'kakao_id'          => $kakaoUser->id,
                        'profile_photo_url' => $profileImage,
                    ]);
                } else {
                    // Create new user only if no user with this email exists
                    $user = User::create([
                        'name'              => $kakaoUser->name ?? $kakaoUser->nickname ?? 'Kakao User',
                        'email'             => $email,
                        'kakao_id'          => $kakaoUser->id,
                        'profile_photo_url' => $profileImage,
                        'password'          => Hash::make(Str::random(32)), // Random password for social login users
                        'email_verified_at' => now(), // Auto-verify social login users
                    ]);
                }
            } else {
                // Update profile photo and name for existing kakao user
                $user->update([
                    'name'              => $kakaoUser->name ?? $kakaoUser->nickname ?? $user->name,
                    'profile_photo_url' => $profileImage,
                ]);
            }

            // Login user
            Auth::login($user, true);

            // mobilescreen=true 파라미터가 있으면 웹뷰용 토큰과 함께 프로필로 이동
            if ($request->session()->get('login.mobilescreen') === 'true') {
                // 웹뷰용 API 토큰 생성
                $token = $user->createToken('webview-kakao-'.now()->timestamp)->plainTextToken;

                // 세션에서 mobilescreen 제거
                $request->session()->forget('login.mobilescreen');

                // 프로필 페이지로 리다이렉트 (토큰 포함)
                return redirect()->route('profile', [
                    'token'        => $token,
                    'hideHeader'   => 'true',
                    'mobilescreen' => 'true',
                ]);
            }

            return redirect()->intended('/');
        } catch (\Exception $e) {
            \Log::error('Kakao login error: '.$e->getMessage());
            \Log::error('Kakao login error trace: '.$e->getTraceAsString());

            return redirect()->route('login')
                ->with('error', 'Kakao login failed. Please try again.');
        }
    }

    /**
     * Handle API Kakao login callback (for mobile app)
     * Returns JSON response with API token
     */
    public function apiCallback(Request $request)
    {
        try {
            $accessToken = $request->input('access_token');
            $userId      = $request->input('user_id');
            $nickname    = $request->input('nickname');

            // 카카오 토큰 검증
            $kakaoUserData = $this->verifyKakaoToken($accessToken);

            if (! $kakaoUserData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Kakao token',
                ], 401);
            }

            // user_id 검증
            if ($kakaoUserData['id'] != $userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User ID verification failed',
                ], 401);
            }

            // 카카오 이메일이 없으면 임시 이메일 생성
            $email           = $kakaoUserData['kakao_account']['email'] ?? $userId.'@kakao.pcaview.com';
            $profileImageUrl = $kakaoUserData['kakao_account']['profile']['profile_image_url'] ?? null;
            $displayName     = $nickname ?? $kakaoUserData['kakao_account']['profile']['nickname'] ?? 'Kakao User';

            // 프로필 이미지를 S3에 저장
            $profileImage = null;
            if ($profileImageUrl) {
                $profileImage = $this->uploadProfileImageToS3($profileImageUrl, $userId);
            }

            // Find user by kakao_id first
            $user = User::where('kakao_id', $userId)->first();

            if (! $user) {
                // Then check if email already exists
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update existing user with Kakao ID and profile photo
                    $user->update([
                        'kakao_id'          => $userId,
                        'profile_photo_url' => $profileImage,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name'              => $displayName,
                        'email'             => $email,
                        'kakao_id'          => $userId,
                        'profile_photo_url' => $profileImage,
                        'password'          => Hash::make(Str::random(32)),
                        'email_verified_at' => now(),
                    ]);
                }
            } else {
                // Update profile photo and name for existing kakao user
                $user->update([
                    'name'              => $displayName,
                    'profile_photo_url' => $profileImage,
                ]);
            }

            // Generate API token
            $token = $user->createToken('mobile-kakao-'.now()->timestamp)->plainTextToken;

            // Return JSON response with token and user info
            return response()->json([
                'success' => true,
                'token'   => $token,
                'user'    => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'email'             => $user->email,
                    'profile_photo_url' => $user->profile_photo_url,
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('API Kakao login error: '.$e->getMessage());
            \Log::error('API Kakao login error trace: '.$e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Kakao login failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Handle mobile app Kakao login callback (legacy web redirect)
     *
     * @deprecated Use apiCallback for mobile apps
     */
    private function handleMobileCallback(Request $request)
    {
        try {
            $accessToken = $request->input('access_token');
            $userId      = $request->input('user_id');
            $nickname    = $request->input('nickname');

            // 카카오 토큰 검증 (선택사항)
            $kakaoUserData = $this->verifyKakaoToken($accessToken);

            if (! $kakaoUserData) {
                \Log::error('Kakao token verification failed');

                return redirect()->route('login')
                    ->with('error', 'Invalid Kakao token');
            }

            // user_id 검증
            if ($kakaoUserData['id'] != $userId) {
                \Log::error('User ID mismatch: expected '.$userId.', got '.$kakaoUserData['id']);

                return redirect()->route('login')
                    ->with('error', 'User ID verification failed');
            }

            // 카카오 이메일이 없으면 임시 이메일 생성
            $email           = $kakaoUserData['kakao_account']['email'] ?? $userId.'@kakao.pcaview.com';
            $profileImageUrl = $kakaoUserData['kakao_account']['profile']['profile_image_url'] ?? null;
            $displayName     = $nickname ?? $kakaoUserData['kakao_account']['profile']['nickname'] ?? 'Kakao User';

            // 프로필 이미지를 S3에 저장
            $profileImage = null;
            if ($profileImageUrl) {
                $profileImage = $this->uploadProfileImageToS3($profileImageUrl, $userId);
            }

            // Find user by kakao_id first
            $user = User::where('kakao_id', $userId)->first();

            if (! $user) {
                // Then check if email already exists
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update existing user with Kakao ID and profile photo
                    $user->update([
                        'kakao_id'          => $userId,
                        'profile_photo_url' => $profileImage,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name'              => $displayName,
                        'email'             => $email,
                        'kakao_id'          => $userId,
                        'profile_photo_url' => $profileImage,
                        'password'          => Hash::make(Str::random(32)),
                        'email_verified_at' => now(),
                    ]);
                }
            } else {
                // Update profile photo and name for existing kakao user
                $user->update([
                    'name'              => $displayName,
                    'profile_photo_url' => $profileImage,
                ]);
            }

            // Login user
            Auth::login($user, true);

            // 모바일 스크린으로 리다이렉트
            $hideHeader   = $request->input('hideHeader', 'true');
            $mobileScreen = $request->input('mobilescreen', 'true');

            return redirect()->route('profile', [
                'hideHeader'   => $hideHeader,
                'mobilescreen' => $mobileScreen,
            ]);
        } catch (\Exception $e) {
            \Log::error('Mobile Kakao login error: '.$e->getMessage());
            \Log::error('Mobile Kakao login error trace: '.$e->getTraceAsString());

            return redirect()->route('login')
                ->with('error', 'Mobile Kakao login failed. Please try again.');
        }
    }

    /**
     * Verify Kakao access token
     */
    private function verifyKakaoToken(string $accessToken): ?array
    {
        try {
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
            ])->get('https://kapi.kakao.com/v2/user/me');

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Kakao token verification failed: '.$response->body());

            return null;
        } catch (\Exception $e) {
            \Log::error('Kakao token verification request error: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Upload Kakao profile image to S3
     */
    private function uploadProfileImageToS3(string $imageUrl, string $userId): ?string
    {
        try {
            // 이미지 다운로드
            $imageContent = \Http::get($imageUrl)->body();

            if (empty($imageContent)) {
                \Log::error('Failed to download Kakao profile image: '.$imageUrl);

                return null;
            }

            // 파일 확장자 추출 (기본값: jpg)
            $extension = 'jpg';
            $parsedUrl = parse_url($imageUrl);
            if (isset($parsedUrl['path'])) {
                $pathInfo = pathinfo($parsedUrl['path']);
                if (isset($pathInfo['extension'])) {
                    $extension = strtolower($pathInfo['extension']);
                }
            }

            // S3에 저장할 파일명 생성
            $filename = 'profile-images/kakao/'.$userId.'_'.time().'.'.$extension;

            // S3에 업로드
            $disk     = Storage::disk('s3');
            $uploaded = $disk->put($filename, $imageContent, 'public');

            if (! $uploaded) {
                \Log::error('Failed to upload Kakao profile image to S3');

                return null;
            }

            // S3 URL 반환
            $s3Url = $disk->url($filename);
            \Log::info('Kakao profile image uploaded to S3: '.$s3Url);

            return $s3Url;
        } catch (\Exception $e) {
            \Log::error('Error uploading Kakao profile image to S3: '.$e->getMessage());
            \Log::error('Error trace: '.$e->getTraceAsString());

            // 실패해도 null 반환 (프로필 이미지 없이 로그인 진행)
            return null;
        }
    }
}
