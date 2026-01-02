<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class ProfileController extends Controller
{
    /**
     * 프로필 페이지 표시
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $allDepartments = Department::all();

        // 사용자가 구독 안 하는 부서 ID 목록
        $unsubscribedDepartmentIds = $user ? $user->departments()->pluck('departments.id')->toArray() : [];

        // 구독하는 부서 = 전체 - 구독 안 하는 부서
        $subscribedDepartmentIds = $allDepartments->pluck('id')->diff($unsubscribedDepartmentIds)->values()->toArray();

        return Inertia::render('Profile', [
            'allDepartments' => $allDepartments,
            'subscribedDepartmentIds' => $subscribedDepartmentIds,
            'canResetPassword' => Route::has('password.request'),
        ]);
    }

    /**
     * 부서 구독/구독 취소
     */
    public function toggleSubscription(Request $request)
    {
        $request->validate([
            'department_id' => 'required|exists:departments,id',
        ]);

        $user = $request->user();
        $departmentId = $request->input('department_id');

        // user_departments는 구독 안 하는 부서를 저장
        // 테이블에 있으면 = 구독 안 함, 없으면 = 구독 중
        if ($user->departments()->where('department_id', $departmentId)->exists()) {
            // 테이블에서 제거 = 구독 시작
            $user->departments()->detach($departmentId);
            $message = '구독되었습니다.';
        } else {
            // 테이블에 추가 = 구독 취소
            $user->departments()->attach($departmentId);
            $message = '구독이 취소되었습니다.';
        }

        return back()->with('success', $message);
    }
}
