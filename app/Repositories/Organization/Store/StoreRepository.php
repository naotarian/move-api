<?php

namespace App\Repositories\Organization\Store;

use App\Models\Store;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class StoreRepository implements StoreRepositoryInterface
{
    /**
     * 指定した組織IDの店舗一覧を取得
     *
     * @param string $organizationId
     * @param array $filters
     * @return Collection
     */
    public function getStoresByOrganizationId(string $organizationId, array $filters = []): Collection
    {
        Log::info('StoreRepository: Getting stores for organization', [
            'organization_id' => $organizationId,
            'filters' => $filters
        ]);

        $query = Store::where('organization_id', $organizationId);

        // 店舗名での絞り込み
        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // メールアドレスでの絞り込み
        if (!empty($filters['email'])) {
            $query->where('email', 'LIKE', '%' . $filters['email'] . '%');
        }

        // 電話番号での絞り込み
        if (!empty($filters['phone'])) {
            $query->where('phone', 'LIKE', '%' . $filters['phone'] . '%');
        }

        // 住所での絞り込み
        if (!empty($filters['address'])) {
            $query->where('address', 'LIKE', '%' . $filters['address'] . '%');
        }

        // ステータスでの絞り込み
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 認証状態での絞り込み
        if (isset($filters['is_verified'])) {
            $query->where('is_verified', (bool)$filters['is_verified']);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * 指定した組織IDのアクティブな店舗一覧を取得
     *
     * @param string $organizationId
     * @return Collection
     */
    public function getActiveStoresByOrganizationId(string $organizationId): Collection
    {
        Log::info('StoreRepository: Getting active stores for organization', [
            'organization_id' => $organizationId
        ]);

        return Store::where('organization_id', $organizationId)
            ->where('status', Store::STATUS_ACTIVE)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * 指定した組織IDの店舗数を取得
     *
     * @param string $organizationId
     * @return int
     */
    public function getStoreCountByOrganizationId(string $organizationId): int
    {
        return Store::where('organization_id', $organizationId)->count();
    }

    /**
     * 指定した組織IDのアクティブな店舗数を取得
     *
     * @param string $organizationId
     * @return int
     */
    public function getActiveStoreCountByOrganizationId(string $organizationId): int
    {
        return Store::where('organization_id', $organizationId)
            ->where('status', Store::STATUS_ACTIVE)
            ->count();
    }

    /**
     * 指定したIDの店舗を取得
     *
     * @param string $id
     * @return Store|null
     */
    public function getStoreById(string $id): ?Store
    {
        return Store::find($id);
    }

    /**
     * 指定したIDの店舗の支払い方法を更新
     *
     * @param string $id
     * @param string $payment_method_id
     * @return bool
     */
    public function updatePaymentMethod(string $id, string $payment_method_id): bool
    {
        Log::info('StoreRepository updatePaymentMethod', ['id' => $id, 'payment_method_id' => $payment_method_id]);
        $result = Store::find($id)->update(['store_default_payment_method_id' => $payment_method_id]);
        Log::info('StoreRepository updatePaymentMethod result', ['id' => $id, 'payment_method_id' => $payment_method_id, 'result' => $result]);
        return $result;
    }
}
