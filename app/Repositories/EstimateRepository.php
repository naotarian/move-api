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
    public function getPaginatedEstimates(int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ])
            ->where('status', Estimate::STATUS_PUBLISHED) // 公開中の見積もりのみ
            ->where('email_verified', true) // メール認証済み
            ->where('phone_verified', true) // 電話認証済み
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * IDで見積もりを取得
     * 店舗用: 公開中かつメール・電話認証済みの見積もりのみ
     */
    public function findById(string $id): ?Estimate
    {
        $estimate = Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage.category'
        ])
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
