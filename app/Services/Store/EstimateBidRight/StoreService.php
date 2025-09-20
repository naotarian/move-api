<?php

namespace App\Services\Store\EstimateBidRight;

use App\Repositories\Store\EstimateBidRight\EstimateBidRightRepositoryInterface as repository;

class StoreService
{
    public function __construct(
        private repository $estimateBidRightRepository
    ) {}

    public function createEstimateBidRight($estimateId, $storeId, $paymentId): void
    {
        $this->estimateBidRightRepository->create($estimateId, $storeId, $paymentId);
    }
}
