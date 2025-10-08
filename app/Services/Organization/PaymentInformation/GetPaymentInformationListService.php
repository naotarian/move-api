<?php

namespace App\Services\Organization\PaymentInformation;

use App\Repositories\Organization\PaymentInformation\PaymentInformationRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;

class GetPaymentInformationListService
{
    public function __construct(
        private PaymentInformationRepositoryInterface $paymentInformationRepository
    ) {}

    public function execute($perPage = 20, $page = 1): array
    {
        $organization = Auth::guard('organization')->user();
        if (!$organization) {
            throw new \Exception('認証されていません');
        }
        $paymentInformation = $this->paymentInformationRepository->getPaymentInformationList($organization->id, $perPage, $page);
        if ($organization->org_default_payment_method_id) {
            $defaultPaymentInformation = $this->paymentInformationRepository->findById($organization->org_default_payment_method_id);
        }
        return $this->formatPaymentInformationList($paymentInformation, $defaultPaymentInformation);
    }

    private function formatPaymentInformationList(LengthAwarePaginator $paymentInformation, $defaultPaymentInformation = null): array
    {
        $formattedCustomers = $paymentInformation->map(function ($paymentInformation) {
            return $this->formatPaymentInformationListItem($paymentInformation);
        });
        return [
            'data' => [
                'default_payment_method' => $defaultPaymentInformation,
                'payment_method_list' => $formattedCustomers,
            ],
            'pagination' => [
                'current_page' => $paymentInformation->currentPage(),
                'last_page' => $paymentInformation->lastPage(),
                'per_page' => $paymentInformation->perPage(),
                'total' => $paymentInformation->total(),
                'from' => $paymentInformation->firstItem(),
                'to' => $paymentInformation->lastItem(),
            ],
        ];
    }

    private function formatPaymentInformationListItem($paymentInformation): array
    {
        return [
            'id' => $paymentInformation->id,
            'organization_id' => $paymentInformation->organization_id,
            'stripe_payment_method_id' => $paymentInformation->stripe_payment_method_id,
            'type' => $paymentInformation->type,
            'brand' => $paymentInformation->brand,
            'last4' => $paymentInformation->last4,
            'exp_year' => $paymentInformation->exp_year,
            'exp_month' => $paymentInformation->exp_month,
            'fingerprint' => $paymentInformation->fingerprint,
            'status' => $paymentInformation->status,
            'billing_name' => $paymentInformation->billing_name,
            'billing_zipcode' => $paymentInformation->billing_zipcode,
            'billing_address' => $paymentInformation->billing_address,
            'billing_email' => $paymentInformation->billing_email,
            'label' => $paymentInformation->label,
            'is_default' => !empty($paymentInformation->organization->org_default_payment_method_id) && $paymentInformation->id === $paymentInformation->organization->org_default_payment_method_id,
        ];
    }
}
