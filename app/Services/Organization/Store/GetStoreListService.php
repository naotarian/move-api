<?php

namespace App\Services\Organization\Store;

use App\Repositories\Organization\Store\StoreRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GetStoreListService
{
    public function __construct(
        private StoreRepositoryInterface $storeRepository
    ) {}

    public function execute(array $filters = []): array
    {
        try {
            // Service層で認証情報を取得
            $organization = Auth::guard('organization')->user();

            if (!$organization) {
                Log::warning('GetStoreListService: No authenticated organization found');
                throw new \Exception('認証されていません');
            }

            Log::info('GetStoreListService: Executing store list retrieval', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->name,
                'filters' => $filters
            ]);

            // Repository層には組織IDとフィルタを渡す
            $stores = $this->storeRepository->getStoresByOrganizationId($organization->id, $filters);
            $activeStoreCount = $this->storeRepository->getActiveStoreCountByOrganizationId($organization->id);
            $totalStoreCount = $this->storeRepository->getStoreCountByOrganizationId($organization->id);

            $formattedStores = $stores->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'email' => $store->email,
                    'phone' => $store->phone,
                    'address' => $store->address,
                    'status' => $store->status,
                    'is_verified' => $store->is_verified,
                    'last_login_at' => $store->last_login_at?->format('Y-m-d H:i:s'),
                    'store_default_payment_method_id' => $store->store_default_payment_method_id,
                    'created_at' => $store->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $store->updated_at->format('Y-m-d H:i:s'),
                ];
            })->toArray();

            Log::info('GetStoreListService: Store list retrieved successfully', [
                'organization_id' => $organization->id,
                'total_stores' => $totalStoreCount,
                'active_stores' => $activeStoreCount,
                'returned_stores' => count($formattedStores)
            ]);

            return [
                'stores' => $formattedStores,
                'summary' => [
                    'total_count' => $totalStoreCount,
                    'active_count' => $activeStoreCount,
                    'inactive_count' => $totalStoreCount - $activeStoreCount,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('GetStoreListService: Error retrieving store list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('店舗一覧の取得に失敗しました');
        }
    }
}
