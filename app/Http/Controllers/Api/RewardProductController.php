<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RewardProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardProductController extends Controller
{
    /**
     * 활성화된 상품 목록 조회 (사용자용)
     */
    public function index(Request $request): JsonResponse
    {
        $query = RewardProduct::query()
            ->active()
            ->inStock();

        // 카테고리 필터링
        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        $products = $query
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * 전체 상품 목록 조회 (관리자용)
     */
    public function all(Request $request): JsonResponse
    {
        $products = RewardProduct::query()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    /**
     * 상품 상세 조회
     */
    public function show(RewardProduct $rewardProduct): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $rewardProduct,
        ]);
    }

    /**
     * 상품 생성
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'price' => 'required|numeric|min:0',
            'gold_grams' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $product = RewardProduct::create($validated);

        return response()->json([
            'success' => true,
            'data' => $product,
            'message' => '상품이 생성되었습니다.',
        ], 201);
    }

    /**
     * 상품 수정
     */
    public function update(Request $request, RewardProduct $rewardProduct): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:500',
            'price' => 'sometimes|required|numeric|min:0',
            'gold_grams' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0',
            'category' => 'nullable|string|max:100',
        ]);

        $rewardProduct->update($validated);

        return response()->json([
            'success' => true,
            'data' => $rewardProduct,
            'message' => '상품이 수정되었습니다.',
        ]);
    }

    /**
     * 상품 삭제
     */
    public function destroy(RewardProduct $rewardProduct): JsonResponse
    {
        $rewardProduct->delete();

        return response()->json([
            'success' => true,
            'message' => '상품이 삭제되었습니다.',
        ]);
    }

    /**
     * 카테고리 목록 조회
     */
    public function categories(): JsonResponse
    {
        $categories = RewardProduct::query()
            ->active()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }
}
