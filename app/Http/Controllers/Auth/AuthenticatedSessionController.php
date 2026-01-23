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
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // mobilescreen=true 파라미터가 있으면 웹뷰용 토큰 브릿지로 이동
        if ($request->input('mobilescreen') === 'true' || $request->session()->get('login.mobilescreen') === 'true') {
            // 웹뷰용 API 토큰 생성
            $user = Auth::user();
            $token = $user->createToken('webview-' . now()->timestamp)->plainTextToken;

            // 세션에서 mobilescreen 제거
            $request->session()->forget('login.mobilescreen');

            // 토큰 브릿지 페이지로 리다이렉트
            return redirect()->route('auth.token-bridge', ['token' => $token]);
        }

        return redirect()->intended('/');
    }

    /**
     * Show token bridge page for webview
     */
    public function tokenBridge(Request $request): Response
    {
        $token = $request->input('token');
        $user = Auth::user();

        return Inertia::render('auth/TokenBridge', [
            'token' => $token,
            'user' => $user,
        ]);
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
