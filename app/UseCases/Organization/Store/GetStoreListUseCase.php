<?php

namespace App\UseCases\Organization\Store;

use Illuminate\Support\Facades\Auth;
use App\Services\Organization\Store\GetStoreListService;

class GetStoreListUseCase
{
    public function __construct(
        private GetStoreListService $getStoreListService
    ) {}

    public function execute(array $filters = []): array
    {
        return $this->getStoreListService->execute($filters);
    }
}
