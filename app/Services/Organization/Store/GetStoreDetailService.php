<?php

namespace App\Services\Organization\Store;

use App\Models\Store;
use App\Repositories\Organization\Store\StoreRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class GetStoreDetailService
{
    public function __construct(
        private StoreRepositoryInterface $storeRepository
    ) {}

    public function execute(string $id): array
    {
        $organization = Auth::guard('organization')->user();

        if (!$organization) {
            throw new \Exception('認証されていません', 401);
        }

        $store = $this->storeRepository->getStoreById($id);

        if (!$store) {
            throw new \Exception('店舗が見つかりません', 404);
        }

        if ($store->organization_id !== $organization->id) {
            throw new \Exception('アクセス権限がありません', 403);
        }

        return $this->formatStore($store);
    }

    private function formatStore(Store $store): array
    {
        return [
            'id' => $store->id,
            'name' => $store->name,
            'email' => $store->email,
            'phone' => $store->phone,
            'address' => $store->address,
            'status' => $store->status,
            'is_verified' => $store->is_verified,
            'last_login_at' => $store->last_login_at,
            'created_at' => $store->created_at,
        ];
    }
}
