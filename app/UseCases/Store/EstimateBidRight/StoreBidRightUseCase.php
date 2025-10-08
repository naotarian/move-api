<?php

namespace App\UseCases\Store\EstimateBidRight;

use App\Services\Store\EstimateBidRight\CreateBidRightService;

class StoreBidRightUseCase
{
    private $createBidRightService;
    public function __construct(CreateBidRightService $createBidRightService)
    {
        $this->createBidRightService = $createBidRightService;
    }

    public function execute(string $estimateId): void
    {
        try {
            $this->createBidRightService->execute($estimateId);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
