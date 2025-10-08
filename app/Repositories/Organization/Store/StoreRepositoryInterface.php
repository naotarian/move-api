<?php

namespace App\Repositories\Organization\Store;

use Illuminate\Support\Collection;
use App\Models\Store;

interface StoreRepositoryInterface
{
    /**
     * 指定した組織IDの店舗一覧を取得
     *
     * @param string $organizationId
     * @param array $filters
     * @return Collection
     */
    public function getStoresByOrganizationId(string $organizationId, array $filters = []): Collection;

    /**
     * 指定した組織IDのアクティブな店舗一覧を取得
     *
     * @param string $organizationId
     * @return Collection
     */
    public function getActiveStoresByOrganizationId(string $organizationId): Collection;

    /**
     * 指定した組織IDの店舗数を取得
     *
     * @param string $organizationId
     * @return int
     */
    public function getStoreCountByOrganizationId(string $organizationId): int;

    /**
     * 指定した組織IDのアクティブな店舗数を取得
     *
     * @param string $organizationId
     * @return int
     */
    public function getActiveStoreCountByOrganizationId(string $organizationId): int;

    /**
     * 指定したIDの店舗を取得
     *
     * @param string $id
     * @return Store|null
     */
    public function getStoreById(string $id): ?Store;

    /**
     * 指定したIDの店舗の支払い方法を更新
     *
     * @param string $id
     * @param string $payment_method_id
     * @return bool
     */
    public function updatePaymentMethod(string $id, string $payment_method_id): bool;
}
