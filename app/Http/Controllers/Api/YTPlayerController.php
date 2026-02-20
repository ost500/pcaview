<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\ytplayer\YTPlayerService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * YTPlayer 앱 API 컨트롤러
 */
class YTPlayerController extends Controller
{
    public function __construct(
        private readonly YTPlayerService $ytPlayerService
    ) {}

    /**
     * 앱 공지사항 조회
     *
     * GET /api/ytplayer/notice
     *
     * @return JsonResponse
     */
    public function notice(): JsonResponse
    {
        $notices = $this->ytPlayerService->getActiveNotices();

        return response()->json([
            'success' => true,
            'data' => $notices->map(fn ($notice) => [
                'id' => $notice->id,
                'title' => $notice->title,
                'content' => $notice->content,
                'priority' => $notice->priority,
                'created_at' => $notice->created_at->toIso8601String(),
            ]),
        ]);
    }

    /**
     * 리워드 목록 조회
     *
     * GET /api/ytplayer/rewards
     *
     * @return JsonResponse
     */
    public function rewards(): JsonResponse
    {
        $rewards = $this->ytPlayerService->getAvailableRewards();

        return response()->json([
            'success' => true,
            'data' => $rewards->map(fn ($reward) => [
                'id' => $reward->id,
                'name' => $reward->name,
                'description' => $reward->description,
                'points_required' => $reward->points_required,
                'image_url' => $reward->image_url,
                'expires_at' => $reward->expires_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * 앱 버전 체크
     *
     * GET /api/ytplayer/version_check?appVersion=1.0.0
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function versionCheck(Request $request): JsonResponse
    {
        $request->validate([
            'appVersion' => 'required|string',
        ]);

        $currentVersion = $request->input('appVersion');
        $versionInfo = $this->ytPlayerService->checkVersion($currentVersion);

        return response()->json([
            'success' => true,
            'data' => $versionInfo,
        ]);
    }

    /**
     * 리워드 적립
     *
     * POST /api/ytplayer/reward
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reward(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
            'reward_type' => 'required|string|in:watch,ad,share',
            'where' => 'nullable|string',
            'video_url' => 'nullable|string',
            'video_time' => 'nullable|integer|min:0',
            'video_stringtime' => 'nullable|string',
        ]);

        $rewardLog = $this->ytPlayerService->logReward($validated);
        $balance = $this->ytPlayerService->getUserBalance($validated['encrypted']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $rewardLog->id,
                'points_earned' => $rewardLog->points_earned,
                'balance' => $balance['balance'],
                'total_earned' => $balance['total_earned'],
                'created_at' => $rewardLog->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * 설치 횟수 전송
     *
     * POST /api/ytplayer/install_count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function installCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referrer' => 'nullable|string',
        ]);

        $installCount = $this->ytPlayerService->logInstall(
            $validated['referrer'] ?? null,
            $request->userAgent(),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $installCount->id,
                'created_at' => $installCount->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * 라이브 카운트 전송
     *
     * POST /api/ytplayer/live_count
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function liveCount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'referrer' => 'nullable|string',
            'session_id' => 'nullable|string',
        ]);

        $liveCount = $this->ytPlayerService->logLiveCount(
            $validated['referrer'] ?? null,
            $request->userAgent(),
            $request->ip(),
            $validated['session_id'] ?? null
        );

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $liveCount->id,
                'active_users' => $this->ytPlayerService->getActiveUserCount(),
                'created_at' => $liveCount->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * 사용자 포인트 잔액 조회
     *
     * GET /api/ytplayer/balance?encrypted=user_hash
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function balance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
        ]);

        $balance = $this->ytPlayerService->getUserBalance($validated['encrypted']);

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }

    /**
     * 리워드 사용 (교환)
     *
     * POST /api/ytplayer/use_reward
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function useReward(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
            'reward_id' => 'required|integer|exists:rewards,id',
        ]);

        try {
            $usage = $this->ytPlayerService->useReward(
                $validated['encrypted'],
                $validated['reward_id']
            );

            $balance = $this->ytPlayerService->getUserBalance($validated['encrypted']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $usage->id,
                    'reward_id' => $usage->reward_id,
                    'reward_name' => $usage->reward->name,
                    'points_spent' => $usage->points_spent,
                    'status' => $usage->status,
                    'balance' => $balance['balance'],
                    'total_spent' => $balance['total_spent'],
                    'created_at' => $usage->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 리워드 사용 내역 조회
     *
     * GET /api/ytplayer/reward_history?encrypted=user_hash
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rewardHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'encrypted' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $history = $this->ytPlayerService->getRewardUsageHistory(
            $validated['encrypted'],
            $validated['limit'] ?? 20
        );

        return response()->json([
            'success' => true,
            'data' => $history->map(fn ($usage) => [
                'id' => $usage->id,
                'reward_name' => $usage->reward->name,
                'points_spent' => $usage->points_spent,
                'status' => $usage->status,
                'created_at' => $usage->created_at->toIso8601String(),
            ]),
        ]);
    }
}
