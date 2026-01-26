<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * 사용자 프로필 정보 조회
     */
    public function index(Request $request)
    {
        $user           = $request->user();
        $allDepartments = Department::all();

        // 사용자가 구독 안 하는 부서 ID 목록
        $unsubscribedDepartmentIds = $user->departments()->pluck('departments.id')->toArray();

        // 구독하는 부서 = 전체 - 구독 안 하는 부서
        $subscribedDepartmentIds = $allDepartments->pluck('id')->diff($unsubscribedDepartmentIds)->values()->toArray();

        return response()->json([
            'success'                  => true,
            'user'                     => $user,
            'allDepartments'           => $allDepartments,
            'subscribedDepartmentIds'  => $subscribedDepartmentIds,
            'unsubscribedDepartmentIds' => $unsubscribedDepartmentIds,
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

        $user         = $request->user();
        $departmentId = $request->input('department_id');

        // user_departments는 구독 안 하는 부서를 저장
        // 테이블에 있으면 = 구독 안 함, 없으면 = 구독 중
        if ($user->departments()->where('department_id', $departmentId)->exists()) {
            // 테이블에서 제거 = 구독 시작
            $user->departments()->detach($departmentId);
            $message      = '구독되었습니다.';
            $isSubscribed = true;
        } else {
            // 테이블에 추가 = 구독 취소
            $user->departments()->attach($departmentId);
            $message      = '구독이 취소되었습니다.';
            $isSubscribed = false;
        }

        return response()->json([
            'success'      => true,
            'message'      => $message,
            'isSubscribed' => $isSubscribed,
        ]);
    }

    /**
     * 프로필 사진 업데이트
     */
    public function updateProfilePhoto(Request $request, ProfilePhotoService $profilePhotoService)
    {
        $request->validate([
            'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        $user = $request->user();

        $url = $profilePhotoService->updateProfilePhoto($user, $request->file('profile_photo'));

        return response()->json([
            'success'           => true,
            'message'           => '프로필 사진이 변경되었습니다.',
            'profile_photo_url' => $url,
            'user'              => $user->fresh(),
        ]);
    }

    /**
     * 계정 삭제
     */
    public function destroy(Request $request, ProfilePhotoService $profilePhotoService)
    {
        $user = $request->user();

        // 프로필 사진 정리
        $profilePhotoService->cleanupOnAccountDeletion($user);

        // 모든 토큰 삭제
        $user->tokens()->delete();

        // 사용자 삭제
        $user->delete();

        return response()->json([
            'success'         => true,
            'message'         => '계정이 삭제되었습니다.',
            'logout_required' => true,
        ]);
    }
}
