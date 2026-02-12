<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParkGolfCourse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParkGolfCourseController extends Controller
{
    /**
     * 파크골프장 검색
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $query = ParkGolfCourse::query();

        // 이름으로 검색
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // 지역으로 필터링
        if ($request->has('region')) {
            $query->where('region', $request->input('region'));
        }

        // 좌표 기반 검색 (반경 내 검색)
        if ($request->has('lat') && $request->has('lon')) {
            $lat = (float) $request->input('lat');
            $lon = (float) $request->input('lon');
            $radius = (float) $request->input('radius', 10); // 기본 10km

            // Haversine formula를 사용한 거리 계산
            $query->selectRaw('
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) + sin(radians(?)) *
                sin(radians(latitude)))) AS distance
            ', [$lat, $lon, $lat])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->having('distance', '<=', $radius)
                ->orderBy('distance');
        }

        // 페이지네이션
        $perPage = min((int) $request->input('per_page', 20), 100);
        $courses = $query->paginate($perPage);

        return response()->json($courses);
    }

    /**
     * 특정 파크골프장 상세 정보
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $course = ParkGolfCourse::findOrFail((int) $id);

        return response()->json($course);
    }

    /**
     * 지역 목록 조회
     *
     * @return JsonResponse
     */
    public function regions(): JsonResponse
    {
        $regions = ParkGolfCourse::select('region')
            ->distinct()
            ->whereNotNull('region')
            ->orderBy('region')
            ->pluck('region');

        return response()->json($regions);
    }

    /**
     * 주변 파크골프장 검색
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:0.1|max:100',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $lat = (float) $request->input('lat');
        $lon = (float) $request->input('lon');
        $radius = (float) $request->input('radius', 10);
        $limit = (int) $request->input('limit', 20);

        $courses = ParkGolfCourse::selectRaw('
            *,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) + sin(radians(?)) *
            sin(radians(latitude)))) AS distance
        ', [$lat, $lon, $lat])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<=', $radius)
            ->orderBy('distance')
            ->limit($limit)
            ->get();

        return response()->json([
            'center' => [
                'lat' => $lat,
                'lon' => $lon,
            ],
            'radius' => $radius,
            'count' => $courses->count(),
            'courses' => $courses,
        ]);
    }

    /**
     * 통계 정보
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => ParkGolfCourse::count(),
            'by_region' => ParkGolfCourse::select('region')
                ->selectRaw('count(*) as count')
                ->whereNotNull('region')
                ->groupBy('region')
                ->orderBy('count', 'desc')
                ->get(),
            'with_coordinates' => ParkGolfCourse::whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->count(),
            'average_holes' => ParkGolfCourse::whereNotNull('holes')
                ->avg('holes'),
        ];

        return response()->json($stats);
    }
}
