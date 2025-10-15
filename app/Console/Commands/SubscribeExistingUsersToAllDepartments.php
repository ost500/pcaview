<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\User;
use Illuminate\Console\Command;

class SubscribeExistingUsersToAllDepartments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:subscribe-all-departments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '기존 사용자들을 모든 부서에 자동 구독시킵니다';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::all();
        $departments = Department::all();
        $departmentIds = $departments->pluck('id');

        $this->info("총 {$users->count()}명의 사용자를 {$departments->count()}개 부서에 구독시킵니다.");

        foreach ($users as $user) {
            $currentSubscriptions = $user->departments()->pluck('departments.id')->toArray();
            $newSubscriptions = $departmentIds->diff($currentSubscriptions);

            if ($newSubscriptions->isNotEmpty()) {
                $user->departments()->attach($newSubscriptions);
                $this->info("사용자 {$user->name} ({$user->email})에게 {$newSubscriptions->count()}개 부서 추가");
            } else {
                $this->info("사용자 {$user->name} ({$user->email})는 이미 모든 부서에 구독되어 있습니다.");
            }
        }

        $this->info('완료!');

        return 0;
    }
}
