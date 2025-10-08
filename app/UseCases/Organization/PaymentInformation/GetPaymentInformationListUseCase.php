<?php

namespace App\UseCases\Organization\PaymentInformation;

use App\Services\Organization\PaymentInformation\GetPaymentInformationListService;

class GetPaymentInformationListUseCase
{
    public function __construct(
        private GetPaymentInformationListService $getPaymentInformationListService
    ) {}

    public function execute($perPage = 20, $page = 1): array
    {
        return $this->getPaymentInformationListService->execute($perPage, $page);
    }
}
