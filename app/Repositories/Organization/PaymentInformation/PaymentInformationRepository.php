<?php

namespace App\Repositories\Organization\PaymentInformation;

use App\Models\OrganizationPaymentMethod;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentInformationRepository implements PaymentInformationRepositoryInterface
{
    public function getPaymentInformationList($organization_id, $perPage = 20, $page = 1): LengthAwarePaginator
    {
        return OrganizationPaymentMethod::where('organization_id', $organization_id)->with('organization')->paginate($perPage, ['*'], 'page', $page);
    }

    public function findById(string $id): ?OrganizationPaymentMethod
    {
        return OrganizationPaymentMethod::find($id);
    }
}
