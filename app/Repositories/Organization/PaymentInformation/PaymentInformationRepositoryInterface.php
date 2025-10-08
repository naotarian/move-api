<?php

namespace App\Repositories\Organization\PaymentInformation;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\OrganizationPaymentMethod;

interface PaymentInformationRepositoryInterface
{
    public function getPaymentInformationList($organization_id, $perPage = 20, $page = 1): LengthAwarePaginator;
    public function findById(string $id): ?OrganizationPaymentMethod;
}
