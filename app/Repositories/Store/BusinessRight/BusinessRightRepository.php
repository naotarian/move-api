<?php

namespace App\Repositories\Store\BusinessRight;

use App\Models\BusinessRight;
use Illuminate\Pagination\LengthAwarePaginator;

class BusinessRightRepository implements BusinessRightRepositoryInterface
{
    public function getBusinessRights(string $storeId, int $perPage = 20, int $page = 1, array $searchConditions = []): LengthAwarePaginator
    {
        $query = BusinessRight::where('store_id', $storeId)
            ->with([
                'estimate.movingFromAddress',
                'estimate.movingToAddress',
                'estimate.luggageItems.luggage.category',
                'bid',
                'store'
            ]);

        // 検索条件を追加
        if (!empty($searchConditions['keyword'])) {
            $keyword = $searchConditions['keyword'];
            $query->whereHas('estimate', function ($q) use ($keyword) {
                $q->where('name', 'like', '%' . $keyword . '%')
                    ->orWhere('phone', 'like', '%' . $keyword . '%')
                    ->orWhere('email', 'like', '%' . $keyword . '%');
            });
        }

        // 順位での絞り込み
        if (!empty($searchConditions['ranking'])) {
            $query->where('ranking', $searchConditions['ranking']);
        }

        return $query->orderBy('granted_at', 'asc')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
