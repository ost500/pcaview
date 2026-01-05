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
            // JavaScript SDK를 사용하므로 Socialite를 통해 인증 코드 처리
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
}
