<?php

namespace App\Services\Store\Bid;

use App\Models\Bid;
use App\Repositories\Store\Bid\BidRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class GetBidsService
{
    public function __construct(
        private BidRepositoryInterface $bidRepository
    ) {}

    /**
     * 指定された見積もりの入札一覧を取得し、レスポンス形式に変換
     *
     * @param string $estimateId
     * @return array
     */
    public function execute(string $estimateId): array
    {
        $bids = $this->bidRepository->findByEstimateId($estimateId);

        $formattedBids = $this->formatBidsForResponse($bids);

        return $formattedBids;
    }

    /**
     * 入札一覧をレスポンス形式に変換
     *
     * @param Collection<Bid> $bids
     * @return array
     */
    private function formatBidsForResponse(Collection $bids): array
    {
        return $bids->map(function ($bid) {
            return $this->formatBidForResponse($bid);
        })->toArray();
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
