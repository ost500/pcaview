<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 로그인 여부 확인
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // 관리자 이메일 목록 확인
        $adminEmails = explode(',', config('admin.emails', ''));
        $adminEmails = array_map('trim', $adminEmails);

        // 현재 사용자 이메일이 관리자 목록에 있는지 확인
        if (! in_array(auth()->user()->email, $adminEmails)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
