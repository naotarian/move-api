<?php

namespace App\UseCases\Organization\Store;

use App\Services\Organization\Store\UpdatePaymentMethodService;

class UpdatePaymentMethodUseCase
{
    public function __construct(
        private UpdatePaymentMethodService $updatePaymentMethodService
    ) {}

    public function execute(string $id, string $payment_method_id): void
    {
        try {
            $this->updatePaymentMethodService->execute($id, $payment_method_id);
        } catch (\Exception $e) {
            throw $e;
        }
        return;
    }
}
