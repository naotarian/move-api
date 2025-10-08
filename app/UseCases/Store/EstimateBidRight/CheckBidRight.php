<?php

namespace App\UseCases\Store\EstimateBidRight;

use App\Services\Store\EstimateBidRight\Check;

class CheckBidRight
{
    private $check;

    public function __construct(Check $check)
    {
        $this->check = $check;
    }

    public function execute(string $estimateId, string $storeId): bool
    {
        return $this->check->execute($estimateId, $storeId);
    }
}
