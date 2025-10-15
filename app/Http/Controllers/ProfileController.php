<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
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

        // 사용자가 구독한 부서 ID 목록
        $subscribedDepartmentIds = $user ? $user->departments()->pluck('departments.id')->toArray() : [];

        return Inertia::render('Profile', [
            'allDepartments' => $allDepartments,
            'subscribedDepartmentIds' => $subscribedDepartmentIds,
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

        // 이미 구독 중인지 확인
        if ($user->departments()->where('department_id', $departmentId)->exists()) {
            // 구독 취소
            $user->departments()->detach($departmentId);
            $message = '구독이 취소되었습니다.';
        } else {
            // 구독
            $user->departments()->attach($departmentId);
            $message = '구독되었습니다.';
        }

        return back()->with('success', $message);
    }
}
