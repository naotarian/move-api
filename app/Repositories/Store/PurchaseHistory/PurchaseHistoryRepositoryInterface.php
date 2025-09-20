<?php

namespace App\Repositories\Store\PurchaseHistory;

use Illuminate\Pagination\LengthAwarePaginator;

interface PurchaseHistoryRepositoryInterface
{
    /**
     * 店舗の購入履歴を取得
     *
     * @param string $storeId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPurchaseHistoryByStore(
        string $storeId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $page = 1,
        int $perPage = 20
    ): LengthAwarePaginator;
}
