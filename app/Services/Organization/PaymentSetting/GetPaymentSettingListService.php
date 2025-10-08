<?php

namespace App\Services\Organization\PaymentSetting;

use App\Repositories\Organization\PaymentSetting\PaymentSettingRepositoryInterface;

class GetPaymentSettingListService
{
    public function __construct(
        private PaymentSettingRepositoryInterface $getPaymentSettingListRepository
    ) {}

    public function execute()
    {
        return $this->getPaymentSettingListRepository->getPaymentSettingList();
    }
}
