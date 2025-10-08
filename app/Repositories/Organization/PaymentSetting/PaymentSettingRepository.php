<?php

namespace App\Repositories\Organization\PaymentSetting;

use App\Models\OrganizationPaymentMethod;
use Illuminate\Pagination\LengthAwarePaginator;

class PaymentSettingRepository implements PaymentSettingRepositoryInterface
{
    public function getPaymentSettingList(): LengthAwarePaginator
    {
        return OrganizationPaymentMethod::paginate(20);
    }
}
