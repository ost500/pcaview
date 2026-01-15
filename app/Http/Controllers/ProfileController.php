<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
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

    /**
     * 프로필 사진 업데이트
     */
    public function updateProfilePhoto(Request $request)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $user = $request->user();

        // 기존 프로필 사진이 S3에 있고 카카오 사진이 아니면 삭제
        if ($user->profile_photo_url && !str_contains($user->profile_photo_url, 'kakaocdn.net')) {
            $path = parse_url($user->profile_photo_url, PHP_URL_PATH);
            if ($path && Storage::disk('s3')->exists(ltrim($path, '/'))) {
                Storage::disk('s3')->delete(ltrim($path, '/'));
            }
        }

        // 새 프로필 사진 업로드
        $path = $request->file('profile_photo')->store('profile-photos', 's3');
        $url = Storage::disk('s3')->url($path);

        // 사용자 프로필 사진 업데이트
        $user->update([
            'profile_photo_url' => $url,
        ]);

        return back()->with('success', '프로필 사진이 변경되었습니다.');
    }

    /**
     * 계정 삭제
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // 프로필 사진이 S3에 있고 카카오 사진이 아니면 삭제
        if ($user->profile_photo_url && !str_contains($user->profile_photo_url, 'kakaocdn.net')) {
            $path = parse_url($user->profile_photo_url, PHP_URL_PATH);
            if ($path && Storage::disk('s3')->exists(ltrim($path, '/'))) {
                Storage::disk('s3')->delete(ltrim($path, '/'));
            }
        }

        // 사용자 삭제 (로그아웃 전에 먼저 삭제)
        $user->delete();

        // 로그아웃
        Auth::logout();

        // 세션 무효화 및 재생성
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', '계정이 삭제되었습니다.');
    }
}
