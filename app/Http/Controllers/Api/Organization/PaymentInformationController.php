<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\UseCases\Organization\PaymentInformation\GetPaymentInformationListUseCase;

class PaymentInformationController extends Controller
{
    public function __construct(
        private GetPaymentInformationListUseCase $getPaymentInformationListUseCase
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);
        $paymentInformation = $this->getPaymentInformationListUseCase->execute($perPage, $page);
        return response()->json($paymentInformation);
    }
}
