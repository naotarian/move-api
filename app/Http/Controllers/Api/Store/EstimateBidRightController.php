<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
// usecase
use App\UseCases\EstimateBidRight\CheckBidRight;

class EstimateBidRightController extends Controller
{
    private $checkBidRight;

    public function __construct(CheckBidRight $checkBidRight)
    {
        $this->checkBidRight = $checkBidRight;
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => '入札権作成に成功しました',
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        \Log::info('request', ['request' => $request->all()]);
        $validator = Validator::make($request->all(), [
            'estimate_id' => 'required|string',
            'store_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'バリデーションエラーが発生しました',
                'errors' => $validator->errors(),
            ], 422);
        }

        // 見積もり情報を取得して購入期限情報も含める
        $estimate = \App\Models\Estimate::find($request->estimate_id);
        $hasBidRight = $this->checkBidRight->execute($request->estimate_id, $request->store_id);

        $responseData = [
            'hasBidRight' => $hasBidRight,
        ];

        // 見積もりが存在する場合は期限情報も追加
        if ($estimate) {
            $responseData['estimate_info'] = [
                'id' => $estimate->id,
                'status' => $estimate->status,
                'bid_deadline' => $estimate->bid_deadline?->format('Y-m-d H:i:s'),
                'purchase_deadline' => $estimate->getBidRightPurchaseDeadline()?->format('Y-m-d H:i:s'),
                'is_within_purchase_deadline' => $estimate->isWithinPurchaseDeadline(),
                'is_purchase_deadline_expired' => $estimate->isPurchaseDeadlineExpired(),
                'remaining_purchase_minutes' => $estimate->remaining_purchase_minutes,
                'is_within_bid_deadline' => $estimate->isWithinBidDeadline(),
                'is_bid_deadline_expired' => $estimate->isBidDeadlineExpired(),
                'remaining_bid_hours' => $estimate->remaining_bid_hours,
            ];
        }

        if (!$hasBidRight) {
            return response()->json([
                'data' => $responseData,
                'message' => '入札権がありません',
            ], 403);
        }

        \Log::info('result', ['result' => $hasBidRight]);
        return response()->json([
            'data' => $responseData,
            'message' => '入札権確認に成功しました',
        ]);
    }
}
