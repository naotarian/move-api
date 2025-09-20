<?php

namespace App\Repositories\Store\PurchaseHistory;

use App\Models\EstimateBidRight;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PurchaseHistoryRepository implements PurchaseHistoryRepositoryInterface
{
    /**
     * 店舗の購入履歴を取得
     *
     * @param string $storeId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $page
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPurchaseHistoryByStore(
        string $storeId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $page = 1,
        int $perPage = 20
    ): LengthAwarePaginator {
        Log::info('Repository: 購入履歴取得開始', [
            'store_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page' => $page,
            'per_page' => $perPage
        ]);

        // クエリを構築
        $query = EstimateBidRight::with([
            'estimate:id',
            'payment:id,amount_including_tax,payment_date,provider,provider_id'
        ])
            ->where('estimate_bid_rights.store_id', $storeId) // テーブル名を明示
            ->where('estimate_bid_rights.status', 1) // 有効な入札権のみ
            ->whereHas('payment'); // 支払いが完了したもののみ

        // 日付フィルタを適用
        if ($startDate || $endDate) {
            $query->whereHas('payment', function ($paymentQuery) use ($startDate, $endDate) {
                if ($startDate) {
                    $paymentQuery->whereDate('payment_date', '>=', $startDate);
                }
                if ($endDate) {
                    $paymentQuery->whereDate('payment_date', '<=', $endDate);
                }
            });
        }

        // 支払い日時の降順でソート（新しいもの順）
        // サブクエリを使用してソート
        $query->orderByDesc(
            DB::table('payments')
                ->select('payment_date')
                ->whereColumn('payments.id', 'estimate_bid_rights.payment_id')
                ->limit(1)
        );

        // ページネーション
        $results = $query->paginate($perPage, ['*'], 'page', $page);

        Log::info('Repository: 購入履歴取得完了', [
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage()
        ]);

        return $results;
    }
}
