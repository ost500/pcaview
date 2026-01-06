<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class KakaoController extends Controller
{
    /**
     * Redirect to Kakao OAuth
     */
    public function redirect()
    {
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
            $email = $kakaoUser->email ?? $kakaoUser->id . '@kakao.pcaview.com';

            // Find user by kakao_id first
            $user = User::where('kakao_id', $kakaoUser->id)->first();

            if (!$user) {
                // Then check if email already exists
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update existing user with Kakao ID and profile photo
                    $user->update([
                        'kakao_id' => $kakaoUser->id,
                        'profile_photo_url' => $kakaoUser->avatar ?? $kakaoUser->avatar_original ?? null,
                    ]);
                } else {
                    // Create new user only if no user with this email exists
                    $user = User::create([
                        'name' => $kakaoUser->name ?? $kakaoUser->nickname ?? 'Kakao User',
                        'email' => $email,
                        'kakao_id' => $kakaoUser->id,
                        'profile_photo_url' => $kakaoUser->avatar ?? $kakaoUser->avatar_original ?? null,
                        'password' => Hash::make(Str::random(32)), // Random password for social login users
                        'email_verified_at' => now(), // Auto-verify social login users
                    ]);
                }
            } else {
                // Update profile photo and name for existing kakao user
                $user->update([
                    'name' => $kakaoUser->name ?? $kakaoUser->nickname ?? $user->name,
                    'profile_photo_url' => $kakaoUser->avatar ?? $kakaoUser->avatar_original ?? null,
                ]);
            }

            // Login user
            Auth::login($user, true);

            // mobilescreen=true 파라미터가 있으면 프로필로 리다이렉트
            if ($request->session()->get('login.mobilescreen') === 'true') {
                return redirect()->route('profile');
            }

            return redirect()->intended('/');
        } catch (\Exception $e) {
            \Log::error('Kakao login error: ' . $e->getMessage());
            \Log::error('Kakao login error trace: ' . $e->getTraceAsString());
            return redirect()->route('login')
                ->with('error', 'Kakao login failed. Please try again.');
        }
    }

    /**
     * Handle mobile app Kakao login callback
     */
    private function handleMobileCallback(Request $request)
    {
        try {
            $accessToken = $request->input('access_token');
            $userId = $request->input('user_id');
            $nickname = $request->input('nickname');

            // 카카오 토큰 검증 (선택사항)
            $kakaoUserData = $this->verifyKakaoToken($accessToken);

            if (!$kakaoUserData) {
                \Log::error('Kakao token verification failed');
                return redirect()->route('login')
                    ->with('error', 'Invalid Kakao token');
            }

            // user_id 검증
            if ($kakaoUserData['id'] != $userId) {
                \Log::error('User ID mismatch: expected ' . $userId . ', got ' . $kakaoUserData['id']);
                return redirect()->route('login')
                    ->with('error', 'User ID verification failed');
            }

            // 카카오 이메일이 없으면 임시 이메일 생성
            $email = $kakaoUserData['kakao_account']['email'] ?? $userId . '@kakao.pcaview.com';
            $profileImage = $kakaoUserData['kakao_account']['profile']['profile_image_url'] ?? null;
            $displayName = $nickname ?? $kakaoUserData['kakao_account']['profile']['nickname'] ?? 'Kakao User';

            // Find user by kakao_id first
            $user = User::where('kakao_id', $userId)->first();

            if (!$user) {
                // Then check if email already exists
                $user = User::where('email', $email)->first();

                if ($user) {
                    // Update existing user with Kakao ID and profile photo
                    $user->update([
                        'kakao_id' => $userId,
                        'profile_photo_url' => $profileImage,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $displayName,
                        'email' => $email,
                        'kakao_id' => $userId,
                        'profile_photo_url' => $profileImage,
                        'password' => Hash::make(Str::random(32)),
                        'email_verified_at' => now(),
                    ]);
                }
            } else {
                // Update profile photo and name for existing kakao user
                $user->update([
                    'name' => $displayName,
                    'profile_photo_url' => $profileImage,
                ]);
            }

            // Login user
            Auth::login($user, true);

            // 모바일 스크린으로 리다이렉트
            $hideHeader = $request->input('hideHeader', 'true');
            $mobileScreen = $request->input('mobilescreen', 'true');

            return redirect()->route('profile', [
                'hideHeader' => $hideHeader,
                'mobilescreen' => $mobileScreen,
            ]);
        } catch (\Exception $e) {
            \Log::error('Mobile Kakao login error: ' . $e->getMessage());
            \Log::error('Mobile Kakao login error trace: ' . $e->getTraceAsString());
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
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get('https://kapi.kakao.com/v2/user/me');

            if ($response->successful()) {
                return $response->json();
            }

            \Log::error('Kakao token verification failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            \Log::error('Kakao token verification request error: ' . $e->getMessage());
            return null;
        }
    }
}
