<?php

namespace App\UseCases\Store\PurchaseHistory;

use App\Services\Store\PurchaseHistory\GetPurchaseHistoryService;

class GetPurchaseHistoryUseCase
{
    public function __construct(
        private GetPurchaseHistoryService $getPurchaseHistoryService
    ) {}

    /**
     * 購入履歴一覧取得のユースケース
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
        // ビジネスルールの検証
        $this->validateStoreId($storeId);
        $this->validateDateRange($startDate, $endDate);
        $this->validatePaginationParams($page, $perPage);

        // Service層の呼び出し
        return $this->getPurchaseHistoryService->execute(
            $storeId,
            $startDate,
            $endDate,
            $page,
            $perPage
        );
    }

    /**
     * 店舗IDの検証
     *
     * @param string $storeId
     * @throws \InvalidArgumentException
     */
    private function validateStoreId(string $storeId): void
    {
        if (empty($storeId)) {
            throw new \InvalidArgumentException('店舗IDが必要です');
        }

        // ULIDの形式チェック（簡易版）
        if (strlen($storeId) !== 26) {
            throw new \InvalidArgumentException('店舗IDの形式が正しくありません');
        }
    }

    /**
     * 日付範囲の検証
     *
     * @param string|null $startDate
     * @param string|null $endDate
     * @throws \InvalidArgumentException
     */
    private function validateDateRange(?string $startDate, ?string $endDate): void
    {
        if ($startDate && !strtotime($startDate)) {
            throw new \InvalidArgumentException('開始日の形式が正しくありません');
        }

        if ($endDate && !strtotime($endDate)) {
            throw new \InvalidArgumentException('終了日の形式が正しくありません');
        }

        if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
            throw new \InvalidArgumentException('開始日は終了日より前である必要があります');
        }

        // 過去3年以内の制限
        if ($startDate && strtotime($startDate) < strtotime('-3 years')) {
            throw new \InvalidArgumentException('開始日は3年以内である必要があります');
        }
    }

    /**
     * ページネーションパラメータの検証
     *
     * @param int $page
     * @param int $perPage
     * @throws \InvalidArgumentException
     */
    private function validatePaginationParams(int $page, int $perPage): void
    {
        if ($page < 1) {
            throw new \InvalidArgumentException('ページ番号は1以上である必要があります');
        }

        if ($perPage < 1 || $perPage > 100) {
            throw new \InvalidArgumentException('1ページあたりの件数は1〜100の範囲で指定してください');
        }
    }
}
