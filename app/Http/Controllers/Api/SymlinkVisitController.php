<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SymlinkVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SymlinkVisitController extends Controller
{
    /**
     * 전체 방문 기록 조회
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = SymlinkVisit::query();

        // ad_id로 필터링
        if ($request->has('ad_id')) {
            $query->where('ad_id', $request->input('ad_id'));
        }

        // 날짜 범위 필터링
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        // 정렬 (최신순)
        $query->orderBy('created_at', 'desc');

        // 페이지네이션
        $perPage = min((int) $request->input('per_page', 50), 100);
        $visits = $query->paginate($perPage);

        return response()->json($visits);
    }

    /**
     * 특정 ad_id의 방문 기록 조회
     *
     * @param string $adId
     * @return JsonResponse
     */
    public function show(string $adId): JsonResponse
    {
        $visit = SymlinkVisit::where('ad_id', $adId)->firstOrFail();

        return response()->json($visit);
    }

    /**
     * 통계 정보 조회
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $query = SymlinkVisit::query();

        // 날짜 범위 필터링
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $stats = [
            'total_visits' => $query->count(),
            'unique_ads' => $query->distinct('ad_id')->count('ad_id'),
            'recent_visits' => SymlinkVisit::orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        // 시간대별 방문 통계 (최근 24시간)
        if ($request->boolean('include_hourly')) {
            $stats['hourly_visits'] = SymlinkVisit::selectRaw('DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDay())
                ->groupBy('hour')
                ->orderBy('hour', 'desc')
                ->get();
        }

        // 일별 방문 통계 (최근 30일)
        if ($request->boolean('include_daily')) {
            $stats['daily_visits'] = SymlinkVisit::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();
        }

        return response()->json($stats);
    }

    /**
     * ad_id별 방문 횟수 집계
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function countByAdId(Request $request): JsonResponse
    {
        $query = SymlinkVisit::query();

        // 날짜 범위 필터링
        if ($request->has('start_date')) {
            $query->where('created_at', '>=', $request->input('start_date'));
        }

        if ($request->has('end_date')) {
            $query->where('created_at', '<=', $request->input('end_date'));
        }

        $counts = $query->selectRaw('ad_id, COUNT(*) as visit_count, MAX(created_at) as last_visit')
            ->whereNotNull('ad_id')
            ->groupBy('ad_id')
            ->orderBy('visit_count', 'desc')
            ->get();

        return response()->json([
            'total_ads' => $counts->count(),
            'ads' => $counts,
        ]);
    }

    /**
     * 방문 기록 생성 (외부 서비스용)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'ad_id' => 'required|string|max:255',
            'ip' => 'nullable|ip',
            'user_agent' => 'nullable|string',
            'referer' => 'nullable|string',
        ]);

        $visit = SymlinkVisit::updateOrCreate(
            ['ad_id' => $request->input('ad_id')],
            [
                'ip' => $request->input('ip') ?? $request->ip(),
                'user_agent' => $request->input('user_agent') ?? $request->userAgent(),
                'referer' => $request->input('referer') ?? $request->header('referer'),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $visit,
        ], 201);
    }

    /**
     * 방문 기록 삭제
     *
     * @param string $adId
     * @return JsonResponse
     */
    public function destroy(string $adId): JsonResponse
    {
        $visit = SymlinkVisit::where('ad_id', $adId)->firstOrFail();
        $visit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Visit record deleted successfully',
        ]);
    }
}
