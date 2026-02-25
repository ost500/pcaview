<?php

declare(strict_types=1);

namespace App\Domain\ytplayer;

use App\Models\AppVersion;
use App\Models\InstallCount;
use App\Models\LiveCount;
use App\Models\Notice;
use App\Models\Reward;
use App\Models\RewardLog;
use App\Models\RewardUsage;
use App\Models\UserReward;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * YTPlayer 앱 관련 비즈니스 로직 서비스
 */
class YTPlayerService
{
    /**
     * 활성화된 공지사항 가져오기
     *
     * @return Collection<Notice>
     */
    public function getActiveNotices(): Collection
    {
        return Notice::where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 사용 가능한 리워드 목록 가져오기
     *
     * @return Collection<Reward>
     */
    public function getAvailableRewards(?string $applicationName = null): Collection
    {
        return Reward::where('is_active', true)
            ->when($applicationName, function ($query) use ($applicationName) {
                $query->whereHas('application', function ($q) use ($applicationName) {
                    $q->where('name', $applicationName);
                });
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('points_required', 'asc')
            ->get();
    }

    /**
     * 앱 버전 체크 및 업데이트 필요 여부 확인
     *
     * @param string $currentVersion 현재 앱 버전 (예: "1.0.0")
     * @return array{
     *     is_update_required: bool,
     *     latest_version: string,
     *     update_url: string|null,
     *     message: string|null
     * }
     */
    public function checkVersion(string $currentVersion): array
    {
        $latestVersion = AppVersion::where('is_active', true)
            ->orderBy('version', 'desc')
            ->first();

        if (! $latestVersion) {
            return [
                'is_update_required' => false,
                'latest_version'     => $currentVersion,
                'update_url'         => null,
                'message'            => null,
            ];
        }

        $isUpdateRequired = version_compare($currentVersion, $latestVersion->version, '<')
            && $latestVersion->is_force_update;

        return [
            'is_update_required' => $isUpdateRequired,
            'latest_version'     => $latestVersion->version,
            'update_url'         => $latestVersion->update_url,
            'message'            => $latestVersion->update_message,
        ];
    }

    /**
     * 리워드 적립 처리
     *
     * @param array{
     *     encrypted: string,
     *     reward_type: string,
     *     where: string|null,
     *     video_url: string|null,
     *     video_time: int|null,
     *     video_stringtime: string|null
     * } $data
     *
     * @throws \Exception
     */
    public function logReward(array $data, ?int $userId = null): RewardLog
    {
        // 중복 적립 방지 체크
        if (config('ytplayer.duplicate_prevention.enabled')) {
            $this->checkDuplicateReward($data['encrypted'], $data['reward_type'], $data['video_url'] ?? null);
        }

        return DB::transaction(function () use ($data, $userId) {
            // 리워드 타입별 포인트 계산
            $pointsEarned = $this->calculateRewardPoints(
                $data['reward_type'],
                $data['video_time'] ?? 0
            );

            // 리워드 로그 생성
            $rewardLog = RewardLog::create([
                'encrypted'        => $data['encrypted'],
                'reward_type'      => $data['reward_type'],
                'where'            => $data['where'] ?? null,
                'video_url'        => $data['video_url'] ?? null,
                'video_time'       => $data['video_time'] ?? null,
                'video_stringtime' => $data['video_stringtime'] ?? null,
                'points_earned'    => $pointsEarned,
            ]);

            // 사용자 리워드 잔액 업데이트
            $userReward = UserReward::firstOrCreate(
                ['encrypted' => $data['encrypted']],
                [
                    'user_id'      => $userId,
                    'balance'      => 0,
                    'total_earned' => 0,
                    'total_spent'  => 0,
                ]
            );

            // 로그인한 유저라면 user_id 업데이트
            if ($userId && ! $userReward->user_id) {
                $userReward->update(['user_id' => $userId]);
            }

            $userReward->increment('balance', $pointsEarned);
            $userReward->increment('total_earned', $pointsEarned);

            // 중복 방지 캐시 저장
            if (config('ytplayer.duplicate_prevention.enabled')) {
                $this->markRewardAsProcessed($data['encrypted'], $data['reward_type'], $data['video_url'] ?? null);
            }

            return $rewardLog;
        });
    }

    /**
     * 중복 적립 체크
     *
     * @throws \Exception
     */
    private function checkDuplicateReward(string $encrypted, string $rewardType, ?string $videoUrl): void
    {
        $cacheKey = $this->getDuplicateCacheKey($encrypted, $rewardType, $videoUrl);

        if (cache()->has($cacheKey)) {
            throw new \Exception('Duplicate reward request detected');
        }
    }

    /**
     * 처리된 리워드로 표시
     */
    private function markRewardAsProcessed(string $encrypted, string $rewardType, ?string $videoUrl): void
    {
        $cacheKey = $this->getDuplicateCacheKey($encrypted, $rewardType, $videoUrl);
        $window   = config('ytplayer.duplicate_prevention.window', 60);

        cache()->put($cacheKey, true, now()->addSeconds($window));
    }

    /**
     * 중복 체크용 캐시 키 생성
     */
    private function getDuplicateCacheKey(string $encrypted, string $rewardType, ?string $videoUrl): string
    {
        $key = "ytplayer:reward:{$encrypted}:{$rewardType}";

        if ($videoUrl) {
            $key .= ':'.md5($videoUrl);
        }

        return $key;
    }

    /**
     * 리워드 포인트 계산
     */
    private function calculateRewardPoints(string $rewardType, int $videoTime): int
    {
        return match ($rewardType) {
            'watch'  => (int) ($videoTime / 60) * 10, // 1분당 10포인트
            'ad'     => 50, // 광고 시청 시 50포인트
            'share'  => 100, // 공유 시 100포인트
            'mining' => 10, // 마이닝 시 10포인트
            default  => 0,
        };
    }

    /**
     * 설치 횟수 기록
     */
    public function logInstall(?string $referrer, ?string $userAgent, ?string $ipAddress): InstallCount
    {
        return InstallCount::create([
            'referrer'   => $referrer,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);
    }

    /**
     * 라이브 카운트 기록
     */
    public function logLiveCount(
        ?string $referrer,
        ?string $userAgent,
        ?string $ipAddress,
        ?string $sessionId
    ): LiveCount {
        return LiveCount::create([
            'referrer'   => $referrer,
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * 사용자별 총 적립 포인트 조회
     */
    public function getTotalPoints(string $encrypted): int
    {
        $userReward = UserReward::where('encrypted', $encrypted)->first();

        return $userReward?->total_earned ?? 0;
    }

    /**
     * 사용자 포인트 잔액 조회
     *
     * @return array{
     *     balance: int,
     *     total_earned: int,
     *     total_spent: int
     * }
     */
    public function getUserBalance(string $encrypted, ?int $userId = null): array
    {
        $userReward = UserReward::where('encrypted', $encrypted)->first();

        if (! $userReward) {
            return [
                'balance'      => 0,
                'total_earned' => 0,
                'total_spent'  => 0,
            ];
        }

        // 로그인한 유저라면 user_id 업데이트
        if ($userId && ! $userReward->user_id) {
            $userReward->update(['user_id' => $userId]);
        }

        return [
            'balance'      => $userReward->balance,
            'total_earned' => $userReward->total_earned,
            'total_spent'  => $userReward->total_spent,
        ];
    }

    /**
     * 리워드 사용 (교환)
     *
     * @throws \Exception
     */
    public function useReward(string $encrypted, int $rewardId, ?int $userId = null): RewardUsage
    {
        return DB::transaction(function () use ($encrypted, $rewardId, $userId) {
            // 사용자 리워드 조회
            $userReward = UserReward::where('encrypted', $encrypted)->lockForUpdate()->first();

            if (! $userReward) {
                throw new \Exception('User reward not found');
            }

            // 로그인한 유저라면 user_id 업데이트
            if ($userId && ! $userReward->user_id) {
                $userReward->update(['user_id' => $userId]);
            }

            // 리워드 조회
            $reward = Reward::findOrFail($rewardId);

            if (! $reward->is_active) {
                throw new \Exception('Reward is not available');
            }

            // 만료 체크
            if ($reward->expires_at && $reward->expires_at < now()) {
                throw new \Exception('Reward has expired');
            }

            // 포인트 부족 체크
            if ($userReward->balance < $reward->points_required) {
                throw new \Exception('Insufficient points');
            }

            // 포인트 차감
            $userReward->decrement('balance', $reward->points_required);
            $userReward->increment('total_spent', $reward->points_required);

            // 사용 내역 생성
            return RewardUsage::create([
                'user_reward_id' => $userReward->id,
                'reward_id'      => $rewardId,
                'points_spent'   => $reward->points_required,
                'status'         => 'completed',
            ]);
        });
    }

    /**
     * 리워드 사용 내역 조회
     *
     * @return Collection<RewardUsage>
     */
    public function getRewardUsageHistory(string $encrypted, int $limit = 20): Collection
    {
        $userReward = UserReward::where('encrypted', $encrypted)->first();

        if (! $userReward) {
            return collect();
        }

        return RewardUsage::with('reward')
            ->where('user_reward_id', $userReward->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 일별 설치 통계
     */
    public function getInstallStatistics(int $days = 7): Collection
    {
        return InstallCount::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();
    }

    /**
     * 실시간 활성 사용자 수 (최근 5분)
     */
    public function getActiveUserCount(): int
    {
        return LiveCount::where('created_at', '>=', now()->subMinutes(5))
            ->distinct('session_id')
            ->count('session_id');
    }
}
