<?php

namespace App\UseCases\Organization\PaymentSetting;

use App\Services\Organization\PaymentSetting\GetPaymentSettingListService;
use App\Services\Organization\PaymentInformation\GetPaymentInformationListService;
use App\Services\Organization\Store\GetStoreListService;
use Illuminate\Support\Facades\Auth;

class GetPaymentSettingListUseCase
{
    public function __construct(
        private GetPaymentSettingListService $getPaymentSettingListService,
        private GetPaymentInformationListService $getPaymentInformationListService,
        private GetStoreListService $getStoreListService
    ) {}

    public function execute()
    {
        $organization = Auth::guard('organization')->user();
        $payment_method_list = $this->getPaymentInformationListService->execute();
        $stores = $this->getStoreListService->execute();
        return ['payment_method_list' => $payment_method_list, 'stores' => $stores];
    }
}
