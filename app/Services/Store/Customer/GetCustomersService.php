<?php

namespace App\Services\Store\Customer;

use Illuminate\Database\Eloquent\Collection;
use App\Repositories\Store\BusinessRight\BusinessRightRepositoryInterface as Repository;
use Illuminate\Pagination\LengthAwarePaginator;

class GetCustomersService
{
    public function __construct(
        private Repository $customerRepository
    ) {}

    /**
     * 顧客一覧取得
     *
     * @param string $storeId
     * @param int $perPage
     * @param int $page
     * @param array $searchConditions
     * @return array
     */
    public function execute(string $storeId, int $perPage = 20, int $page = 1, array $searchConditions = []): array
    {
        $customers = $this->customerRepository->getBusinessRights($storeId, $perPage, $page, $searchConditions);
        return $this->formatCustomersForResponse($customers);
    }

    private function formatCustomersForResponse(LengthAwarePaginator $customers): array
    {
        $formattedCustomers = $customers->map(function ($customer) {
            return $this->formatCustomerListItem($customer);
        });
        return [
            'data' => $formattedCustomers,
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
        ];
    }

    private function formatCustomerListItem($customer): array
    {
        return [
            'id' => $customer->id,
            'estimate_id' => $customer->estimate_id,
            'store_id' => $customer->store_id,
            'bid_id' => $customer->bid_id,
            'bid_amount_min' => $customer->bid_amount_min,
            'bid_amount_max' => $customer->bid_amount_max,
            'ranking' => $customer->ranking,
            'is_notified' => $customer->is_notified,
            'granted_at' => $customer->granted_at,
            'estimate' => $customer->estimate ? [
                ...$customer->estimate->toArray(),
                'moving_from' => $customer->estimate->movingFromAddress,
                'moving_to' => $customer->estimate->movingToAddress,
                'luggage_by_category' => $this->formatLuggageByCategory($customer->estimate->luggageItems),
            ] : null,
            'store' => $customer->store,
            'bid' => $customer->bid,
        ];
    }

    /**
     * 荷物をカテゴリー別にグループ化
     *
     * @param \Illuminate\Database\Eloquent\Collection $luggageItems
     * @return array
     */
    private function formatLuggageByCategory($luggageItems): array
    {
        if (!$luggageItems) {
            return [];
        }

        $groupedLuggage = [];

        foreach ($luggageItems as $item) {
            $categoryName = $item->luggage->category->name ?? 'その他';

            if (!isset($groupedLuggage[$categoryName])) {
                $groupedLuggage[$categoryName] = [];
            }

            $groupedLuggage[$categoryName][] = [
                'name' => $item->luggage->name,
                'quantity' => $item->quantity,
                'sub_label' => $item->luggage->sub_label ?? null,
            ];
        }

        return $groupedLuggage;
    }
}
