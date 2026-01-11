<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Contents;
use Illuminate\Http\Request;

class ChurchContentsController extends Controller
{
    /**
     * Get all contents for a specific church
     *
     * @param  string  $churchSlug  Church slug (e.g., 'maple', 'goldang')
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, string $churchSlug)
    {
        // Church를 slug로 찾기
        $church = Church::where('slug', $churchSlug)->firstOrFail();

        // Query 시작
        $query = Contents::where('church_id', $church->id)
            ->with(['department', 'user', 'departments']);

        // Department 필터 (옵션)
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        // 정렬 (기본: 최신순)
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 페이지네이션
        $perPage = $request->input('per_page', 20);
        $contents = $query->paginate($perPage);

        return response()->json([
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'display_name' => $church->display_name,
                'slug' => $church->slug,
            ],
            'contents' => $contents,
        ]);
    }

    /**
     * Get all contents for a specific church by church ID
     *
     * @param  int  $churchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function byId(Request $request, int $churchId)
    {
        // Church를 ID로 찾기
        $church = Church::findOrFail($churchId);

        // Query 시작
        $query = Contents::where('church_id', $church->id)
            ->with(['department', 'user', 'departments']);

        // Department 필터 (옵션)
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        // 정렬 (기본: 최신순)
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 페이지네이션
        $perPage = $request->input('per_page', 20);
        $contents = $query->paginate($perPage);

        return response()->json([
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'display_name' => $church->display_name,
                'slug' => $church->slug,
            ],
            'contents' => $contents,
        ]);
    }

    /**
     * Get video contents only for a specific church by slug
     *
     * @param  string  $churchSlug
     * @return \Illuminate\Http\JsonResponse
     */
    public function videos(Request $request, string $churchSlug)
    {
        // Church를 slug로 찾기
        $church = Church::where('slug', $churchSlug)->firstOrFail();

        // Query 시작 - file_type이 video인 것만
        $query = Contents::where('church_id', $church->id)
            ->with(['department', 'user', 'departments']);

        // Department 필터 (옵션)
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        // 정렬 (기본: 최신순)
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 페이지네이션
        $perPage = $request->input('per_page', 20);
        $contents = $query->paginate($perPage);

        return response()->json([
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'display_name' => $church->display_name,
                'slug' => $church->slug,
            ],
            'contents' => $contents,
        ]);
    }

    /**
     * Get video contents only for a specific church by church ID
     *
     * @param  int  $churchId
     * @return \Illuminate\Http\JsonResponse
     */
    public function videosByChurchId(Request $request, int $churchId)
    {
        // Church를 ID로 찾기
        $church = Church::findOrFail($churchId);

        // Query 시작 - file_type이 video인 것만
        $query = Contents::where('church_id', $church->id)
            ->where('file_type', 'video')
            ->whereNotNull('file_url')
            ->with(['department', 'user', 'departments']);

        // Department 필터 (옵션)
        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        // 정렬 (기본: 최신순)
        $sortBy = $request->input('sort_by', 'published_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 페이지네이션
        $perPage = $request->input('per_page', 20);
        $contents = $query->paginate($perPage);

        return response()->json([
            'church' => [
                'id' => $church->id,
                'name' => $church->name,
                'display_name' => $church->display_name,
                'slug' => $church->slug,
            ],
            'contents' => $contents,
        ]);
    }
}
