<?php

namespace App\UseCases\Organization\Store;

use App\Services\Organization\Store\GetStoreDetailService;

class GetStoreDetailUseCase
{
    public function __construct(
        private GetStoreDetailService $getStoreDetailService
    ) {}

    public function execute(string $id): array
    {
        try {
            return $this->getStoreDetailService->execute($id);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
