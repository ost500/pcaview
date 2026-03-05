<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NoticeController extends Controller
{
    /**
     * 활성화된 공지사항 목록 조회 (피드용)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Notice::query()
            ->where('is_active', true);

        // 현재 시간 기준으로 노출 기간 필터링
        $now = now();
        $query->where(function ($q) use ($now) {
            $q->whereNull('start_at')
                ->orWhere('start_at', '<=', $now);
        });
        $query->where(function ($q) use ($now) {
            $q->whereNull('end_at')
                ->orWhere('end_at', '>=', $now);
        });

        $notices = $query
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notices,
        ]);
    }

    /**
     * 전체 공지사항 목록 조회 (관리자용)
     */
    public function all(Request $request): JsonResponse
    {
        $notices = Notice::query()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notices,
        ]);
    }

    /**
     * 공지사항 상세 조회
     */
    public function show(Notice $notice): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $notice,
        ]);
    }

    /**
     * 공지사항 생성
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $notice = Notice::create($validated);

        return response()->json([
            'success' => true,
            'data' => $notice,
            'message' => '공지사항이 생성되었습니다.',
        ], 201);
    }

    /**
     * 공지사항 수정
     */
    public function update(Request $request, Notice $notice): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'priority' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after:start_at',
        ]);

        $notice->update($validated);

        return response()->json([
            'success' => true,
            'data' => $notice,
            'message' => '공지사항이 수정되었습니다.',
        ]);
    }

    /**
     * 공지사항 삭제
     */
    public function destroy(Notice $notice): JsonResponse
    {
        $notice->delete();

        return response()->json([
            'success' => true,
            'message' => '공지사항이 삭제되었습니다.',
        ]);
    }
}
