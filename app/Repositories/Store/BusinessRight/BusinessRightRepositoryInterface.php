<?php

namespace App\Repositories\Store\BusinessRight;

use Illuminate\Pagination\LengthAwarePaginator;

interface BusinessRightRepositoryInterface
{
    public function getBusinessRights(string $storeId, int $perPage = 20, int $page = 1, array $searchConditions = []): LengthAwarePaginator;
}
