<?php

namespace App\Services\Store\PurchaseHistory;

use App\Repositories\Store\PurchaseHistory\PurchaseHistoryRepositoryInterface;
use App\Models\EstimateBidRight;
use Illuminate\Support\Facades\Log;

class GetPurchaseHistoryService
{
    public function __construct(
        private PurchaseHistoryRepositoryInterface $purchaseHistoryRepository
    ) {}

    /**
     * 店舗の入札権購入履歴を取得
     *
     * @param string $storeId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function execute(
        string $storeId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        Log::info('Service: 購入履歴取得処理開始', [
            'store_id' => $storeId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'page' => $page,
            'per_page' => $perPage
        ]);

        // Repository層の呼び出し
        $results = $this->purchaseHistoryRepository->getPurchaseHistoryByStore(
            $storeId,
            $startDate,
            $endDate,
            $page,
            $perPage
        );

        Log::info('Service: 購入履歴取得処理完了', [
            'total' => $results->total(),
            'current_page' => $results->currentPage(),
            'per_page' => $results->perPage()
        ]);

        return [
            'data' => $this->formatPurchaseHistoryList($results->items()),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'from' => $results->firstItem(),
                'to' => $results->lastItem(),
            ]
        ];
    }

    /**
     * 購入履歴データをレスポンス形式に変換
     *
     * @param array $purchaseHistory
     * @return array
     */
    private function formatPurchaseHistoryList(array $purchaseHistory): array
    {
        return array_map(function ($bidRight) {
            return $this->formatPurchaseHistoryItem($bidRight);
        }, $purchaseHistory);
    }

    /**
     * 購入履歴アイテムをレスポンス形式に変換
     *
     * @param EstimateBidRight $bidRight
     * @return array
     */
    private function formatPurchaseHistoryItem(EstimateBidRight $bidRight): array
    {
        $estimate = $bidRight->estimate;
        $payment = $bidRight->payment;

        return [
            'id' => $bidRight->id,
            'estimate_id' => $estimate->id,
            'payment_id' => $payment->id,
            'estimate_info' => [
                'id' => $estimate->id,
            ],
            'payment_info' => [
                'id' => $payment->id,
                'amount' => $payment->amount_including_tax,
                'payment_date' => $payment->payment_date->format('Y-m-d H:i:s'),
                'provider' => $payment->provider,
                'provider_id' => $payment->provider_id,
            ],
            'purchased_at' => $payment->payment_date->format('Y-m-d H:i:s'),
            'receipt_available' => true, // 将来の領収書機能用
        ];
    }
}
