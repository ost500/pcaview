<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        // intended 파라미터가 있으면 세션에 저장
        if ($request->has('intended')) {
            $request->session()->put('url.intended', $request->input('intended'));
        }

        // mobilescreen 파라미터가 있으면 세션에 저장
        if ($request->has('mobilescreen')) {
            $request->session()->put('login.mobilescreen', $request->input('mobilescreen'));
        }

        return Inertia::render('auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $mobilescreen = $request->input('mobilescreen') === 'true' || $request->session()->get('login.mobilescreen') === 'true';

        \Log::info('Login completed', [
            'mobilescreen_param'   => $request->input('mobilescreen'),
            'mobilescreen_session' => $request->session()->get('login.mobilescreen'),
            'is_mobilescreen'      => $mobilescreen,
        ]);

        // mobilescreen=true 파라미터가 있으면 웹뷰용 토큰과 함께 프로필로 이동
        if ($mobilescreen) {
            // 웹뷰용 API 토큰 생성
            $user  = Auth::user();
            $token = $user->createToken('webview-'.now()->timestamp)->plainTextToken;

            \Log::info('Redirecting to profile with token', [
                'token_length' => strlen($token),
                'user_id'      => $user->id,
            ]);

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
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
