<?php

namespace App\Repositories\Store\Bid;

use App\Models\Bid;
use Illuminate\Database\Eloquent\Collection;

interface BidRepositoryInterface
{
    /**
     * 指定された見積もりの入札一覧を取得
     *
     * @param string $estimateId
     * @return Collection<Bid>
     */
    public function findByEstimateId(string $estimateId): Collection;

    /**
     * 指定された見積もりと店舗の入札を取得
     *
     * @param string $estimateId
     * @param string $storeId
     * @return Bid|null
     */
    public function findByEstimateIdAndStoreId(string $estimateId, string $storeId): ?Bid;

    /**
     * 入札を作成
     *
     * @param array $data
     * @return Bid
     */
    public function create(array $data): Bid;

    /**
     * 入札を更新
     *
     * @param Bid $bid
     * @param array $data
     * @return Bid
     */
    public function update(Bid $bid, array $data): Bid;

    /**
     * IDで入札を取得
     *
     * @param string $id
     * @return Bid|null
     */
    public function findById(string $id): ?Bid;

    /**
     * 店舗IDで入札一覧を取得
     *
     * @param string $storeId
     * @return Collection<Bid>
     */
    public function findByStoreId(string $storeId): Collection;
}
