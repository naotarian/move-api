<?php

namespace App\Repositories\Organization\PaymentSetting;

use Illuminate\Pagination\LengthAwarePaginator;

interface PaymentSettingRepositoryInterface
{
    public function getPaymentSettingList(): LengthAwarePaginator;
}
