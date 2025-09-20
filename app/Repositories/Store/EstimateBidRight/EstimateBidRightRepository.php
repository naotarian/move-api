<?php

namespace App\Repositories\Store\EstimateBidRight;

use App\Models\EstimateBidRight;

class EstimateBidRightRepository implements EstimateBidRightRepositoryInterface
{
    public function check(string $estimateId, string $storeId): bool
    {
        // まず見積もりが認証済みかつ公開中かチェック
        $estimate = \App\Models\Estimate::where('id', $estimateId)
            ->where('status', \App\Models\Estimate::STATUS_PUBLISHED)
            ->where('email_verified', true)
            ->where('phone_verified', true)
            ->first();

        if (!$estimate) {
            \Log::warning('入札権チェック: 見積もりが見つからないか認証未完了', [
                'estimate_id' => $estimateId,
                'store_id' => $storeId
            ]);
            return false;
        }

        // 入札権の存在をチェック
        $estimateBidRight = EstimateBidRight::where('estimate_id', $estimateId)
            ->where('store_id', $storeId)
            ->where('status', EstimateBidRight::STATUS_ACTIVE)
            ->exists();

        \Log::info('入札権チェック結果', [
            'estimate_id' => $estimateId,
            'store_id' => $storeId,
            'estimate_verified' => true,
            'has_bid_right' => $estimateBidRight
        ]);

        return $estimateBidRight;
    }

    public function create(string $estimateId, string $storeId, string $paymentId): void
    {
        EstimateBidRight::create([
            'estimate_id' => $estimateId,
            'store_id' => $storeId,
            'status' => EstimateBidRight::STATUS_ACTIVE,
            'payment_id' => $paymentId,
        ]);
    }

    public function findByEstimateIdAndStoreId(string $estimateId, string $storeId): ?EstimateBidRight
    {
        return EstimateBidRight::where('estimate_id', $estimateId)
            ->where('store_id', $storeId)
            ->where('status', EstimateBidRight::STATUS_ACTIVE)
            ->first();
    }
}
