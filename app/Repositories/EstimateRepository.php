<?php

namespace App\Repositories;

use App\Models\Estimate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EstimateRepository
{
    /**
     * 見積もり一覧を取得（ページネーション付き）
     * 店舗用: 公開中かつメール・電話認証済みの見積もりのみ
     */
    public function getPaginatedEstimates(string $storeId, int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category',
            'estimateBidRights.bid',
        ])
            ->selectRaw('estimates.*, 
                EXISTS(
                    SELECT 1 FROM estimate_bid_rights 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ? 
                    AND estimate_bid_rights.status = 1
                ) as has_bid_right,
                EXISTS(
                    SELECT 1 FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                ) as has_bid,
                (
                    SELECT bids.bid_amount_min FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                    LIMIT 1
                ) as bid_amount_min,
                (
                    SELECT bids.bid_amount_max FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                    LIMIT 1
                ) as bid_amount_max', [$storeId, $storeId, $storeId, $storeId])
            ->where('status', Estimate::STATUS_PUBLISHED) // 公開中の見積もりのみ
            ->where('email_verified', true) // メール認証済み
            ->where('phone_verified', true) // 電話認証済み
            ->where('bid_deadline', '>=', now()) // 入札期限切れではない
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * IDで見積もりを取得
     * 店舗用: 公開中かつメール・電話認証済みの見積もりのみ
     */
    public function findById(string $id, ?string $storeId = null): ?Estimate
    {
        $query = Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ]);

        // 店舗IDが指定された場合は入札権の有無と入札の有無、入札金額を追加
        if ($storeId) {
            $query->selectRaw('estimates.*, 
                EXISTS(
                    SELECT 1 FROM estimate_bid_rights 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ? 
                    AND estimate_bid_rights.status = 1
                ) as has_bid_right,
                EXISTS(
                    SELECT 1 FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                ) as has_bid,
                (
                    SELECT bids.bid_amount_min FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                    LIMIT 1
                ) as bid_amount_min,
                (
                    SELECT bids.bid_amount_max FROM bids 
                    INNER JOIN estimate_bid_rights ON bids.estimate_bid_right_id = estimate_bid_rights.id 
                    WHERE estimate_bid_rights.estimate_id = estimates.id 
                    AND estimate_bid_rights.store_id = ?
                    LIMIT 1
                ) as bid_amount_max', [$storeId, $storeId, $storeId, $storeId]);
        }

        $estimate = $query
            ->where('id', $id)
            ->where('status', Estimate::STATUS_PUBLISHED) // 公開中の見積もりのみ
            ->where('email_verified', true) // メール認証済み
            ->where('phone_verified', true) // 電話認証済み
            ->first();

        return $estimate;
    }

    /**
     * 見積もりを作成
     */
    public function create(array $data): Estimate
    {
        return Estimate::create($data);
    }

    /**
     * 見積もりを更新
     */
    public function update(string $id, array $data): bool
    {
        $estimate = $this->findById($id);
        if (!$estimate) {
            return false;
        }

        return $estimate->update($data);
    }

    /**
     * 見積もりを削除
     */
    public function delete(string $id): bool
    {
        $estimate = $this->findById($id);
        if (!$estimate) {
            return false;
        }

        return $estimate->delete();
    }

    /**
     * 見積もり一覧を取得（全件）
     */
    public function getAll(): Collection
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * ステータス別に見積もり一覧を取得
     */
    public function getByStatus(string $status): Collection
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ])
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 見積もり件数を取得
     */
    public function getCount(): int
    {
        return Estimate::count();
    }

    /**
     * ステータス別見積もり件数を取得
     */
    public function getCountByStatus(string $status): int
    {
        return Estimate::where('status', $status)->count();
    }
}
