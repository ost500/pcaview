<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // user_departments 테이블의 로직을 반전
        // 기존: 구독하는 부서를 저장
        // 변경: 구독 안 하는 부서를 저장

        // 모든 사용자와 모든 부서 가져오기
        $users = DB::table('users')->get();
        $allDepartmentIds = DB::table('departments')->pluck('id');

        foreach ($users as $user) {
            // 해당 사용자가 현재 구독 중인 부서
            $subscribedDepartmentIds = DB::table('user_departments')
                ->where('user_id', $user->id)
                ->pluck('department_id');

            // 구독하지 않는 부서 = 전체 - 구독 중인 부서
            $unsubscribedDepartmentIds = $allDepartmentIds->diff($subscribedDepartmentIds);

            // 기존 데이터 삭제
            DB::table('user_departments')
                ->where('user_id', $user->id)
                ->delete();

            // 구독하지 않는 부서만 저장
            foreach ($unsubscribedDepartmentIds as $departmentId) {
                DB::table('user_departments')->insert([
                    'user_id' => $user->id,
                    'department_id' => $departmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 롤백 시 다시 원래 로직으로 복원
        $users = DB::table('users')->get();
        $allDepartmentIds = DB::table('departments')->pluck('id');

        foreach ($users as $user) {
            // 현재 구독하지 않는 부서 (새 로직)
            $unsubscribedDepartmentIds = DB::table('user_departments')
                ->where('user_id', $user->id)
                ->pluck('department_id');

            // 구독하는 부서 = 전체 - 구독하지 않는 부서
            $subscribedDepartmentIds = $allDepartmentIds->diff($unsubscribedDepartmentIds);

            // 기존 데이터 삭제
            DB::table('user_departments')
                ->where('user_id', $user->id)
                ->delete();

            // 구독하는 부서만 저장 (원래 로직)
            foreach ($subscribedDepartmentIds as $departmentId) {
                DB::table('user_departments')->insert([
                    'user_id' => $user->id,
                    'department_id' => $departmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
