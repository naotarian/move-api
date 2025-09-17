<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LuggageCategory;
use App\Models\LuggageMaster;
use Illuminate\Http\JsonResponse;

class LuggageController extends Controller
{
    /**
     * カテゴリーデータを取得
     */
    public function categories(): JsonResponse
    {
        $categories = LuggageCategory::active()
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'nameEn' => $category->name_en,
                    'description' => $category->description,
                    'sortOrder' => $category->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * 荷物マスタデータを取得
     */
    public function master(): JsonResponse
    {
        $luggageItems = LuggageMaster::with('category')
            ->active()
            ->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'subLabel' => $item->sub_label,
                    'categoryId' => $item->category_id,
                    'categoryCode' => $item->category->code,
                    'categoryName' => $item->category->name,
                    'description' => $item->description,
                    'basePrice' => $item->base_price,
                    'sortOrder' => $item->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $luggageItems,
        ]);
    }

    /**
     * 統合データを取得（カテゴリー別にグループ化）
     */
    public function combined(): JsonResponse
    {
        $categories = LuggageCategory::with(['luggageItems' => function ($query) {
            $query->active()->ordered();
        }])
            ->active()
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'nameEn' => $category->name_en,
                    'description' => $category->description,
                    'sortOrder' => $category->sort_order,
                    'items' => $category->luggageItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'code' => $item->code,
                            'name' => $item->name,
                            'subLabel' => $item->sub_label,
                            'description' => $item->description,
                            'basePrice' => $item->base_price,
                            'sortOrder' => $item->sort_order,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * 特定のカテゴリーの荷物を取得
     */
    public function byCategory(string $categoryCode): JsonResponse
    {
        $category = LuggageCategory::where('code', $categoryCode)
            ->where('is_active', true)
            ->first();

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
            ], 404);
        }

        $items = LuggageMaster::where('category_id', $category->id)
            ->active()
            ->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'subLabel' => $item->sub_label,
                    'description' => $item->description,
                    'basePrice' => $item->base_price,
                    'sortOrder' => $item->sort_order,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'category' => [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'nameEn' => $category->name_en,
                    'description' => $category->description,
                    'sortOrder' => $category->sort_order,
                ],
                'items' => $items,
            ],
        ]);
    }
}
