<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\UseCases\Store\PurchaseHistory\GetPurchaseHistoryUseCase;

class PurchaseHistoryController extends Controller
{
    public function __construct(
        private GetPurchaseHistoryUseCase $getPurchaseHistoryUseCase
    ) {}

    /**
     * 入札権購入履歴一覧を取得
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // バリデーション
            $validator = Validator::make($request->all(), [
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラー',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // 認証されたユーザー（店舗）のIDを取得
            $storeId = Auth::id();

            if (!$storeId) {
                return response()->json([
                    'success' => false,
                    'message' => '認証が必要です',
                ], 401);
            }

            // パラメータを取得
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $page = (int) $request->input('page', 1);
            $perPage = (int) $request->input('per_page', 20);

            // デフォルトで直近3ヶ月
            if (!$startDate && !$endDate) {
                $endDate = now()->format('Y-m-d');
                $startDate = now()->subMonths(3)->format('Y-m-d');
            }

            // UseCase層の呼び出し
            $result = $this->getPurchaseHistoryUseCase->execute(
                $storeId,
                $startDate,
                $endDate,
                $page,
                $perPage
            );

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('購入履歴取得エラー: ' . $e->getMessage(), [
                'store_id' => Auth::id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '購入履歴の取得に失敗しました',
            ], 500);
        }
    }
}
