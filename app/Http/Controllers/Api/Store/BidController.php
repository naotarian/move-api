<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\UseCases\Store\Bid\GetBidsUseCase;
use App\UseCases\Store\Bid\CreateBidUseCase;
use App\UseCases\Store\Bid\GetMyBidUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Store\CreateBidRequest;

class BidController extends Controller
{
    public function __construct(
        private GetBidsUseCase $getBidsUseCase,
        private CreateBidUseCase $createBidUseCase,
        private GetMyBidUseCase $getMyBidUseCase
    ) {}

    /**
     * 指定された見積もりの入札一覧を取得
     */
    public function index(Request $request, string $estimateId): JsonResponse
    {
        try {
            $bids = $this->getBidsUseCase->execute($estimateId);

            return response()->json([
                'data' => $bids,
                'message' => 'Bids retrieved successfully'
            ], 200);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Controller: Invalid argument for get bids', [
                'estimate_id' => $estimateId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Invalid request',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Controller: Failed to get bids', [
                'estimate_id' => $estimateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve bids',
                'message' => 'システムエラーが発生しました'
            ], 500);
        }
    }

    /**
     * 入札を作成
     */
    public function store(CreateBidRequest $request, string $estimateId): JsonResponse
    {
        try {
            // 路径パラメータのバリデーション（ULID形式）
            if (empty($estimateId) || strlen($estimateId) !== 26 || !ctype_alnum($estimateId)) {
                return response()->json([
                    'error' => 'Invalid estimate ID',
                    'message' => '見積もりIDの形式が正しくありません'
                ], 400);
            }

            // 認証されたユーザー（店舗）のIDを取得
            $storeId = Auth::id();

            // ULIDの形式チェック
            if (empty($storeId) || strlen($storeId) !== 26 || !ctype_alnum($storeId)) {
                return response()->json([
                    'error' => 'Invalid store authentication',
                    'message' => '店舗認証が無効です'
                ], 401);
            }

            $bidData = [
                'minPrice' => $request->input('minPrice'),
                'maxPrice' => $request->input('maxPrice'),
                'message' => $request->input('message'),
            ];

            $result = $this->createBidUseCase->execute($estimateId, $storeId, $bidData);

            $statusCode = ($result['action'] === 'created') ? 201 : 200;

            return response()->json([
                'data' => $result,
                'message' => "Bid {$result['action']} successfully"
            ], $statusCode);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Controller: Validation failed for create bid', [
                'estimate_id' => $estimateId,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Controller: Failed to create bid', [
                'estimate_id' => $estimateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to create bid',
                'message' => 'システムエラーが発生しました'
            ], 500);
        }
    }

    /**
     * 自分の入札を取得
     */
    public function getMyBid(Request $request, string $estimateId): JsonResponse
    {
        try {
            // 認証されたユーザー（店舗）のIDを取得
            $storeId = Auth::id();

            $bid = $this->getMyBidUseCase->execute($estimateId, $storeId);

            if (!$bid) {

                return response()->json([
                    'data' => null,
                    'message' => '入札がありません'
                ], 200);
            }

            Log::info('Controller: My bid retrieved successfully', ['bid_id' => $bid['id']]);

            return response()->json([
                'data' => $bid,
                'message' => 'My bid retrieved successfully'
            ], 200);
        } catch (\InvalidArgumentException $e) {
            Log::warning('Controller: Invalid argument for get my bid', [
                'estimate_id' => $estimateId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Invalid request',
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Controller: Failed to get my bid', [
                'estimate_id' => $estimateId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve my bid',
                'message' => 'システムエラーが発生しました'
            ], 500);
        }
    }
}
