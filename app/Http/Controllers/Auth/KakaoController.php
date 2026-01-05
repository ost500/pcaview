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
    public function callback()
    {
        try {
            $kakaoUser = Socialite::driver('kakao')->user();

            // Find or create user
            $user = User::where('kakao_id', $kakaoUser->id)->first();

            if (!$user) {
                // Check if email already exists
                $user = User::where('email', $kakaoUser->email)->first();

                if ($user) {
                    // Update existing user with Kakao ID
                    $user->update([
                        'kakao_id' => $kakaoUser->id,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'name' => $kakaoUser->name ?? $kakaoUser->nickname ?? 'Kakao User',
                        'email' => $kakaoUser->email ?? $kakaoUser->id . '@kakao.pcaview.com',
                        'kakao_id' => $kakaoUser->id,
                        'password' => Hash::make(Str::random(32)), // Random password for social login users
                        'email_verified_at' => now(), // Auto-verify social login users
                    ]);
                }
            }

            // Login user
            Auth::login($user, true);

            return redirect()->intended('/');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Kakao login failed. Please try again.');
        }
    }
}
