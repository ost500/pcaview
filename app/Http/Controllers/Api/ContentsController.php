<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Church;
use App\Models\Contents;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentsController extends Controller
{
    /**
     * Get all contents for a specific church by church name.
     *
     * @param string $church Church name parameter
     */
    public function getByChurch(Request $request, string $church): JsonResponse
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
            ->with(['images', 'departments', 'tags'])
            ->orderBy('published_at', 'desc')
            ->orderBy('created_at', 'desc');

        if ($departmentId) {
            $contentsQuery->whereHas('departments', function ($query) use ($departmentId) {
                $query->where('departments.id', $departmentId);
            });
        }

        $contents = $contentsQuery->get();

        return response()->json([
            'success' => true,
            'message' => 'Contents retrieved successfully',
            'data'    => [
                'church' => [
                    'id'   => $churchModel->id,
                    'name' => $churchModel->name,
                    'slug' => $churchModel->slug,
                ],
                'contents' => $contents,
                'total'    => $contents->count(),
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
                'church' => [
                    'id'   => $churchModel->id,
                    'name' => $churchModel->name,
                    'slug' => $churchModel->slug,
                ],
                'departments' => $departments,
                'total'       => $departments->count(),
            ],
        ]);
    }
}
