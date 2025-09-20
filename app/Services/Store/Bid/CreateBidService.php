<?php

namespace App\Services\Store\Bid;

use App\Models\Bid;
use App\Repositories\Store\Bid\BidRepositoryInterface;
use App\Repositories\Store\EstimateBidRight\EstimateBidRightRepositoryInterface;
use Illuminate\Support\Facades\Log;

class CreateBidService
{
    public function __construct(
        private BidRepositoryInterface $bidRepository,
        private EstimateBidRightRepositoryInterface $estimateBidRightRepository
    ) {}

    /**
     * 入札を作成または更新
     *
     * @param string $estimateId
     * @param string $storeId
     * @param array $bidData
     * @return array
     */
    public function execute(string $estimateId, string $storeId, array $bidData): array
    {
        // まず見積もりが認証済みかつ公開中かチェック
        $estimate = \App\Models\Estimate::where('id', $estimateId)
            ->where('status', \App\Models\Estimate::STATUS_PUBLISHED)
            ->where('email_verified', true)
            ->where('phone_verified', true)
            ->first();

        if (!$estimate) {
            Log::warning('入札作成: 見積もりが見つからないか認証未完了', [
                'estimate_id' => $estimateId,
                'store_id' => $storeId
            ]);
            throw new \Exception('この見積もりは入札できません（認証が完了していない可能性があります）');
        }

        // 入札期限をチェック
        if ($estimate->isBidDeadlineExpired()) {
            Log::warning('入札作成: 入札期限切れ', [
                'estimate_id' => $estimateId,
                'store_id' => $storeId,
                'bid_deadline' => $estimate->bid_deadline?->toDateTimeString(),
                'current_time' => now()->toDateTimeString()
            ]);
            throw new \Exception('この見積もりの入札期限が過ぎています');
        }

        if (!$estimate->isWithinBidDeadline()) {
            Log::warning('入札作成: 入札期限が設定されていないか期限外', [
                'estimate_id' => $estimateId,
                'store_id' => $storeId,
                'bid_deadline' => $estimate->bid_deadline?->toDateTimeString(),
            ]);
            throw new \Exception('この見積もりは現在入札を受け付けていません');
        }

        // 入札権の存在確認
        $estimateBidRight = $this->estimateBidRightRepository->findByEstimateIdAndStoreId($estimateId, $storeId);

        Log::info('Service: EstimateBidRight search result', [
            'found' => $estimateBidRight !== null,
            'estimate_id' => $estimateId,
            'store_id' => $storeId,
            'status' => $estimateBidRight?->status,
            'is_active' => $estimateBidRight?->isActive(),
            'estimate_verified' => true
        ]);

        if (!$estimateBidRight) {
            throw new \Exception('有効な入札権が見つかりません');
        }

        // 既存の入札があるかチェック
        $existingBid = $this->bidRepository->findByEstimateIdAndStoreId($estimateId, $storeId);

        $bidPayload = [
            'estimate_bid_right_id' => $estimateBidRight->id,
            'bid_amount_min' => $bidData['minPrice'],
            'bid_amount_max' => $bidData['maxPrice'],
            'bid_at' => now(),
        ];

        if ($existingBid) {
            // 既存の入札を更新
            Log::info('Service: Updating existing bid', ['bid_id' => $existingBid->id]);
            $bid = $this->bidRepository->update($existingBid, $bidPayload);
            $action = 'updated';
            // ユーザーに入札があったことを知らせるメール送信処理
        } else {
            // 新しい入札を作成
            Log::info('Service: Creating new bid');
            $bid = $this->bidRepository->create($bidPayload);
            $action = 'created';
            // ユーザーに入札があったことを知らせるメール送信処理
        }

        $formattedBid = $this->formatBidForResponse($bid);
        $formattedBid['action'] = $action;

        return $formattedBid;
    }

    /**
     * 入札データをレスポンス形式に変換
     *
     * @param Bid $bid
     * @return array
     */
    private function formatBidForResponse(Bid $bid): array
    {
        return [
            'id' => $bid->id,
            'store_id' => $bid->estimateBidRight->store->id ?? null,
            'store_name' => $bid->estimateBidRight->store->name ?? null,
            'min_price' => $bid->bid_amount_min,
            'max_price' => $bid->bid_amount_max,
            'message' => null, // メッセージフィールドがない場合
            'created_at' => $bid->bid_at->toISOString(),
            'is_winner' => false, // TODO: 落札者判定ロジックを実装
        ];
    }
}
