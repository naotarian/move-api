<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\UseCases\Estimate\GetEstimatesListUseCase;
use App\UseCases\Estimate\GetEstimateDetailUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EstimateController extends Controller
{
    public function __construct(
        private GetEstimatesListUseCase $getEstimatesListUseCase,
        private GetEstimateDetailUseCase $getEstimateDetailUseCase
    ) {}

    /**
     * 見積もり一覧取得（店舗用）
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // ログイン中の店舗のIDを取得
            $storeId = Auth::id();
            $perPage = $request->get('per_page', 20);
            $page = $request->get('page', 1);

            // UseCaseを実行
            $result = $this->getEstimatesListUseCase->execute($storeId, $perPage, $page);
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
            ]);
        } catch (\Exception $e) {
            \Log::error('見積もり一覧取得コントローラーエラー: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '見積もり一覧の取得に失敗しました',
            ], 500);
        }
    }

    /**
     * 見積もり詳細取得
     */
    public function show(string $id): JsonResponse
    {
        try {
            // ログイン中の店舗のIDを取得
            $storeId = Auth::id();

            // UseCaseを実行
            $estimate = $this->getEstimateDetailUseCase->execute($id, $storeId);

            if (!$estimate) {
                return response()->json([
                    'success' => false,
                    'message' => '見積もりが見つかりません',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $estimate,
            ]);
        } catch (\Exception $e) {
            \Log::error('見積もり詳細取得コントローラーエラー: ' . $e->getMessage(), [
                'estimate_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '見積もり詳細の取得に失敗しました',
            ], 500);
        }
    }
}
