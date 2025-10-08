<?php

namespace App\Services\Organization\Store;

use App\Repositories\Organization\Store\StoreRepositoryInterface;
use App\Repositories\Organization\PaymentInformation\PaymentInformationRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class UpdatePaymentMethodService
{
    public function __construct(
        private StoreRepositoryInterface $storeRepository,
        private PaymentInformationRepositoryInterface $paymentInformationRepository
    ) {}

    public function execute(string $id, string $payment_method_id): void
    {
        $organization = Auth::guard('organization')->user();
        if (!$organization) {
            throw new \Exception('認証されていません', 401);
        }
        $payment_method = $this->paymentInformationRepository->findById($payment_method_id);
        if (!$payment_method) {
            throw new \Exception('支払い方法が見つかりません', 404);
        }
        if ($payment_method->organization_id !== $organization->id) {
            throw new \Exception('アクセス権限がありません', 403);
        }
        \Log::info('UpdatePaymentMethodService execute', ['id' => $id, 'payment_method_id' => $payment_method_id]);
        $this->storeRepository->updatePaymentMethod($id, $payment_method_id);
        return;
    }
}
