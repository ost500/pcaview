<?php

declare(strict_types=1);

namespace App\Domain\ytplayer;

use App\Domain\gold\GoldPriceService;
use App\Enums\RewardUsageStatus;
use App\Models\Application;
use App\Models\AppVersion;
use App\Models\DomesticMetalPrice;
use App\Models\InstallCount;
use App\Models\LiveCount;
use App\Models\Notice;
use App\Models\Reward;
use App\Models\RewardBalance;
use App\Models\RewardLog;
use App\Models\RewardProduct;
use App\Models\RewardUsage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
     *     application: string,
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
            // 애플리케이션 조회
            $application = Application::where('name', $data['application'])->first();

            // 리워드 타입별 포인트 계산
            $pointsEarned = $this->calculateRewardPoints(
                $data['reward_type'],
                $data['video_time'] ?? 0,
                $application?->id
            );

            // 사용자 리워드 잔액 업데이트
            $userReward = RewardBalance::firstOrCreate(
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

            // 적립 전 잔액 저장
            $beforeBalance = $userReward->balance;

            $userReward->increment('balance', $pointsEarned);
            $userReward->increment('total_earned', $pointsEarned);

            // 적립 후 잔액
            $afterBalance = $userReward->fresh()->balance;

            // 현재 금 시세 ID 조회
            $currentGoldPrice = DomesticMetalPrice::getLatest();

            // 금 시세가 없으면 API에서 가져오기
            if (! $currentGoldPrice) {
                Log::info('No gold price found in DB, fetching from API...');
                $goldPriceService = app(GoldPriceService::class);
                $currentGoldPrice = $goldPriceService->fetchAndSaveLatestPrice();

                if (! $currentGoldPrice) {
                    Log::warning('Failed to fetch gold price from API, using default value');
                }
            }

            // 한돈(3.75g) 시세를 1g 시세로 변환
            $goldPricePerGram = $currentGoldPrice?->s_pure ? $currentGoldPrice->s_pure / 3.75 : 0;

            // 포인트와 잔액의 금 가치 계산 (금 시세 적용)
            $pointsValue       = $goldPricePerGram > 0 ? $pointsEarned * $goldPricePerGram : 0;
            $afterBalanceValue = $goldPricePerGram > 0 ? $afterBalance * $goldPricePerGram : 0;

            // 리워드 로그 생성 (잔액 정보 및 금 시세 ID 포함)
            $rewardLog = RewardLog::create([
                'encrypted'               => $data['encrypted'],
                'reward_type'             => $data['reward_type'],
                'where'                   => $data['where'] ?? null,
                'video_url'               => $data['video_url'] ?? null,
                'video_time'              => $data['video_time'] ?? null,
                'video_stringtime'        => $data['video_stringtime'] ?? null,
                'points_earned'           => $pointsEarned,
                'points_value'            => $pointsValue,
                'before_balance'          => $beforeBalance,
                'after_balance'           => $afterBalance,
                'after_balance_value'     => $afterBalanceValue,
                'metal_domestic_price_id' => $currentGoldPrice?->id,
            ]);

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
    private function calculateRewardPoints(string $rewardType, int $videoTime, ?int $applicationId = null): float
    {
        // rewards 테이블에서 적립용 리워드 찾기
        $query = Reward::where('code', $rewardType)
            ->where('type', 'accumulation')
            ->where('is_active', true);

        // application_id가 있으면 필터링
        if ($applicationId) {
            $query->where('application_id', $applicationId);
        }

        $reward = $query->first();

        if ($reward) {
            // watch의 경우 시청 시간에 비례
            if ($rewardType === 'watch' && $videoTime > 0) {
                $pointsPerMinute = (float) $reward->points_required;

                return ($videoTime / 60) * $pointsPerMinute;
            }

            // 나머지는 고정 포인트
            return (float) $reward->points_required;
        }

        // 테이블에 없으면 기본값 (하위 호환성)
        return match ($rewardType) {
            'watch'  => ($videoTime / 60) * 10, // 1분당 10포인트
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
        $userReward = RewardBalance::where('encrypted', $encrypted)->first();

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
        $userReward = RewardBalance::where('encrypted', $encrypted)->first();

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
    public function useReward(string $encrypted, int $rewardId, ?int $userId = null, ?int $rewardProductId = null): RewardUsage
    {
        return DB::transaction(function () use ($encrypted, $rewardId, $userId, $rewardProductId) {
            // 사용자 리워드 조회
            $userReward = RewardBalance::where('encrypted', $encrypted)->lockForUpdate()->first();

            if (! $userReward) {
                throw new \Exception('User reward not found');
            }

            // 로그인한 유저라면 user_id 업데이트
            if ($userId && ! $userReward->user_id) {
                $userReward->update(['user_id' => $userId]);
            }

            $pointsRequired = 0;

            // RewardProduct 구매
            if ($rewardProductId) {
                $product = RewardProduct::findOrFail($rewardProductId);

                if (! $product->is_active) {
                    throw new \Exception('Product is not available');
                }

                // 재고 체크
                if ($product->stock <= 0) {
                    throw new \Exception('Product is out of stock');
                }

                $pointsRequired = $product->gold_grams;

                // 포인트 부족 체크
                if ($userReward->balance < $pointsRequired) {
                    throw new \Exception('Insufficient points');
                }

                // 재고 차감
                $product->decrement('stock');
            } else {
                // 기존 Reward 구매
                $reward = Reward::findOrFail($rewardId);

                if (! $reward->is_active) {
                    throw new \Exception('Reward is not available');
                }

                // 만료 체크
                if ($reward->expires_at && $reward->expires_at < now()) {
                    throw new \Exception('Reward has expired');
                }

                $pointsRequired = $reward->points_required;

                // 포인트 부족 체크
                if ($userReward->balance < $pointsRequired) {
                    throw new \Exception('Insufficient points');
                }
            }

            // 사용 전 잔액 저장
            $beforeBalance = $userReward->balance;

            // 포인트 차감
            $userReward->decrement('balance', $pointsRequired);
            $userReward->increment('total_spent', $pointsRequired);

            // 사용 후 잔액
            $afterBalance = $userReward->fresh()->balance;

            // 현재 금 시세 ID 조회
            $currentGoldPrice = DomesticMetalPrice::getLatest();

            // 금 시세가 없으면 API에서 가져오기
            if (! $currentGoldPrice) {
                Log::info('No gold price found in DB, fetching from API...');
                $goldPriceService = app(GoldPriceService::class);
                $currentGoldPrice = $goldPriceService->fetchAndSaveLatestPrice();

                if (! $currentGoldPrice) {
                    Log::warning('Failed to fetch gold price from API, using default value');
                }
            }

            // 한돈(3.75g) 시세를 1g 시세로 변환
            $goldPricePerGram = $currentGoldPrice?->s_pure ? $currentGoldPrice->s_pure / 3.75 : 0;

            // 포인트와 잔액의 금 가치 계산 (금 시세 적용)
            $pointsValue       = $goldPricePerGram > 0 ? $pointsRequired * $goldPricePerGram : 0;
            $afterBalanceValue = $goldPricePerGram > 0 ? $afterBalance * $goldPricePerGram : 0;

            // 리워드 로그 생성 (사용 내역 기록) - 음수로 기록
            RewardLog::create([
                'encrypted'               => $encrypted,
                'reward_type'             => $rewardProductId ? 'use_product' : 'use_reward',
                'where'                   => $rewardProductId ? "product_id:{$rewardProductId}" : "reward_id:{$rewardId}",
                'video_url'               => null,
                'video_time'              => null,
                'video_stringtime'        => null,
                'points_earned'           => -$pointsRequired, // 음수로 기록 (사용)
                'points_value'            => -$pointsValue,
                'before_balance'          => $beforeBalance,
                'after_balance'           => $afterBalance,
                'after_balance_value'     => $afterBalanceValue,
                'metal_domestic_price_id' => $currentGoldPrice?->id,
            ]);

            // 사용 내역 생성 (기본값 pending)
            return RewardUsage::create([
                'user_id'           => $userId,
                'reward_id'         => $rewardProductId ? null : $rewardId,
                'reward_product_id' => $rewardProductId,
                'points_spent'      => $pointsRequired,
                'status'            => RewardUsageStatus::PENDING,
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
        $userReward = RewardBalance::where('encrypted', $encrypted)->first();

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
