<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Contents;
use App\Services\ContentsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentsController extends Controller
{
    /**
     * Get all contents for a specific church by church name.
     *
     * @param string $church Church name parameter
     */
    public function getByChurch(Request $request, string $church, ContentsService $contentsService): JsonResponse
    {
        // Church를 slug으로 찾기
        $churchModel = Church::where('slug', $church)->first();

        if (! $churchModel) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found',
                'data'    => [],
            ], 404);
        }

        $departmentId = $request->input('department_id');

        // 해당 church의 모든 contents 가져오기 (숨김 제외, 최신순)
        $contentsQuery = Contents::where('church_id', $churchModel->id)
            ->where('is_hide', false)
            ->with(['user', 'images', 'departments', 'tags'])
            ->withCount('comments')
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc');

        if ($departmentId) {
            $contentsQuery->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('departments.id', $departmentId);
            });
        }

        $contents = $contentsQuery->get();

        // news 타입 본문 필터링 (저작권 보호를 위해 body의 1/3만 표시)
        $filteredContents = $contentsService->filterNewsContents($contents);

        return response()->json([
            'success' => true,
            'message' => 'Contents retrieved successfully',
            'data'    => [
                'church' => $churchModel,
                'contents' => $filteredContents,
                'total'    => $filteredContents->count(),
            ],
        ]);
    }

    /**
     * Get all departments for a specific church by church name.
     *
     * @param string $church Church name parameter
     */
    public function getDepartments(string $church): JsonResponse
    {
        // Church를 slug으로 찾기
        $churchModel = Church::where('slug', $church)->first();

        if (! $churchModel) {
            return response()->json([
                'success' => false,
                'message' => 'Church not found',
                'data'    => [],
            ], 404);
        }

        // 해당 church의 모든 departments 가져오기
        $departments = $churchModel->departments()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Departments retrieved successfully',
            'data'    => [
                'church' => $churchModel,
                'departments' => $departments,
                'total'       => $departments->count(),
            ],
        ]);
    }

    /**
     * Get a single content by its ID.
     *
     * @param int $id The ID of the content.
     */
    public function show(int $id): JsonResponse
    {
        $content = Contents::with(['user', 'church', 'departments', 'images', 'tags', 'comments'])
            ->find($id);

        if (! $content) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Content retrieved successfully',
            'data'    => $content,
        ]);
    }

    /**
     * Delete a content by its ID.
     *
     * @param  Request  $request
     * @param  int  $id  The ID of the content.
     * @param  ContentsService  $contentsService
     */
    public function destroy(Request $request, int $id, ContentsService $contentsService): JsonResponse
    {
        $contents = Contents::with('church', 'images')->find($id);

        if (! $contents) {
            return response()->json([
                'success' => false,
                'message' => 'Content not found',
            ], 404);
        }

        try {
            // 콘텐츠 삭제
            $contentsService->deleteContents($contents, $request->user());

            return response()->json([
                'success' => true,
                'message' => '콘텐츠가 삭제되었습니다.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }
}
