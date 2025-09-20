<?php

namespace App\Repositories\Store\Payment;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    /**
     * 支払い情報を作成
     */
    public function create(array $data): Payment;

    /**
     * IDで支払い情報を取得
     */
    public function findById(string $id): ?Payment;

    /**
     * 見積もりIDで支払い情報を取得
     */
    public function findByEstimateId(string $estimateId): array;

    /**
     * 店舗IDで支払い情報を取得
     */
    public function findByStoreId(string $storeId): array;

    /**
     * プロバイダーIDで支払い情報を取得
     */
    public function findByProviderId(string $providerId): ?Payment;

    /**
     * 支払いステータスで支払い情報を取得
     */
    public function findByStatus(int $status): array;

    /**
     * 支払い情報を更新
     */
    public function update(string $id, array $data): bool;

    /**
     * 支払い情報を削除
     */
    public function delete(string $id): bool;

    /**
     * 見積もりと店舗の組み合わせで支払い情報を取得
     */
    public function findByEstimateAndStore(string $estimateId, string $storeId): array;

    /**
     * 成功した支払いのみを取得
     */
    public function findSuccessfulPayments(): array;

    /**
     * 失敗した支払いのみを取得
     */
    public function findFailedPayments(): array;
}
