<?php

namespace App\UseCases\Store\Customer;

use Illuminate\Support\Facades\Auth;
use App\Services\Store\Customer\GetCustomersService;

class GetCustomersUseCase
{
    public function __construct(
        private GetCustomersService $getCustomersService
    ) {}

    /**
     * 顧客一覧取得
     *
     * @param int $perPage
     * @param int $page
     * @param array $searchConditions
     * @return array
     */
    public function execute(int $perPage = 20, int $page = 1, array $searchConditions = []): array
    {
        // ログイン中のStoreのIDを取得
        $storeId = Auth::id();
        return $this->getCustomersService->execute($storeId, $perPage, $page, $searchConditions);
    }
}
