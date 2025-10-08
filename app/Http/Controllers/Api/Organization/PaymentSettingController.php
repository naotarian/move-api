<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use App\UseCases\Organization\PaymentSetting\GetPaymentSettingListUseCase;

class PaymentSettingController extends Controller
{
    public function __construct(
        private GetPaymentSettingListUseCase $getPaymentSettingListUseCase
    ) {}

    public function index()
    {
        $paymentSetting = $this->getPaymentSettingListUseCase->execute();
        return response()->json($paymentSetting);
    }
}
