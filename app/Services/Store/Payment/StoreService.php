<?php

namespace App\Services\Store\Payment;
// repository
use App\Repositories\Store\Payment\PaymentRepositoryInterface as repository;

class StoreService
{
    public function __construct(
        private repository $paymentRepository
    ) {}

    public function createPayment(array $data)
    {
        $payment = $this->paymentRepository->create($data);
        return $payment;
    }
}
