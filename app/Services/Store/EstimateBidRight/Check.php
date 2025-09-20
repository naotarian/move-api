<?php

namespace App\Services\Store\EstimateBidRight;

use App\Repositories\Store\EstimateBidRight\EstimateBidRightRepositoryInterface;

class Check
{
    private $estimateBidRightRepository;

    public function __construct(EstimateBidRightRepositoryInterface $estimateBidRightRepository)
    {
        $this->estimateBidRightRepository = $estimateBidRightRepository;
    }

    public function execute(string $estimateId, string $storeId): bool
    {
        return $this->estimateBidRightRepository->check($estimateId, $storeId);
    }
}
