<?php

namespace App\UseCases\Store\Bid;

use App\Services\Store\Bid\CreateBidService;
use Illuminate\Support\Facades\Log;

class CreateBidUseCase
{
    public function __construct(
        private CreateBidService $createBidService
    ) {}

    /**
     * 入札を作成
     *
     * @param string $estimateId
     * @param string $storeId
     * @param array $bidData
     * @return array
     */
    public function execute(string $estimateId, string $storeId, array $bidData): array
    {
        $result = $this->createBidService->execute($estimateId, $storeId, $bidData);

        return $result;
    }
}
