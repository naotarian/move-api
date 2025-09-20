<?php

namespace App\Repositories\Store\EstimateBidRight;

interface EstimateBidRightRepositoryInterface
{
    public function check(string $estimateId, string $storeId): bool;
    public function create(string $estimateId, string $storeId, string $paymentId): void;
    public function findByEstimateIdAndStoreId(string $estimateId, string $storeId): ?\App\Models\EstimateBidRight;
}
