<?php

namespace App\Services\Store\Bid;

use App\Models\Bid;
use App\Repositories\Store\Bid\BidRepositoryInterface;
use Illuminate\Support\Facades\Log;

class GetMyBidService
{
    public function __construct(
        private BidRepositoryInterface $bidRepository
    ) {}

    /**
     * 指定された見積もりと店舗の入札を取得
     *
     * @param string $estimateId
     * @param string $storeId
     * @return array|null
     */
    public function execute(string $estimateId, string $storeId): ?array
    {
        $bid = $this->bidRepository->findByEstimateIdAndStoreId($estimateId, $storeId);

        if (!$bid) {
            return null;
        }

        $formattedBid = $this->formatBidForResponse($bid);

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
