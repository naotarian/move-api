<?php

namespace App\Repositories\Store\Bid;

use App\Models\Bid;
use App\Models\EstimateBidRight;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class BidRepository implements BidRepositoryInterface
{
    /**
     * 指定された見積もりの入札一覧を取得
     *
     * @param string $estimateId
     * @return Collection<Bid>
     */
    public function findByEstimateId(string $estimateId): Collection
    {
        $bids = Bid::with(['estimateBidRight.store'])
            ->whereHas('estimateBidRight', function ($query) use ($estimateId) {
                $query->where('estimate_id', $estimateId);
            })
            ->orderBy('bid_amount_min', 'asc')
            ->orderBy('bid_amount_max', 'asc')
            ->get();

        return $bids;
    }

    /**
     * 指定された見積もりと店舗の入札を取得
     *
     * @param string $estimateId
     * @param string $storeId
     * @return Bid|null
     */
    public function findByEstimateIdAndStoreId(string $estimateId, string $storeId): ?Bid
    {
        $bid = Bid::with(['estimateBidRight'])
            ->whereHas('estimateBidRight', function ($query) use ($estimateId, $storeId) {
                $query->where('estimate_id', $estimateId)
                    ->where('store_id', $storeId);
            })
            ->first();

        return $bid;
    }

    /**
     * 入札を作成
     *
     * @param array $data
     * @return Bid
     */
    public function create(array $data): Bid
    {
        Log::info('Repository: Creating bid', ['data' => $data]);

        $bid = Bid::create($data);

        Log::info('Repository: Bid created', ['bid_id' => $bid->id]);

        return $bid;
    }

    /**
     * 入札を更新
     *
     * @param Bid $bid
     * @param array $data
     * @return Bid
     */
    public function update(Bid $bid, array $data): Bid
    {
        Log::info('Repository: Updating bid', [
            'bid_id' => $bid->id,
            'data' => $data
        ]);

        $bid->update($data);
        $bid->refresh();

        Log::info('Repository: Bid updated', ['bid_id' => $bid->id]);

        return $bid;
    }

    /**
     * IDで入札を取得
     *
     * @param string $id
     * @return Bid|null
     */
    public function findById(string $id): ?Bid
    {
        Log::info('Repository: Finding bid by ID', ['bid_id' => $id]);

        $bid = Bid::with(['estimateBidRight.store'])->find($id);

        Log::info('Repository: Bid search result', ['found' => $bid !== null]);

        return $bid;
    }

    /**
     * 店舗IDで入札一覧を取得
     *
     * @param string $storeId
     * @return Collection<Bid>
     */
    public function findByStoreId(string $storeId): Collection
    {
        Log::info('Repository: Finding bids by store ID', ['store_id' => $storeId]);

        $bids = Bid::with(['estimateBidRight'])
            ->whereHas('estimateBidRight', function ($query) use ($storeId) {
                $query->where('store_id', $storeId);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('Repository: Bids found', ['count' => $bids->count()]);

        return $bids;
    }
}
