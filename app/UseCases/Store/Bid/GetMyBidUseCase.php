<?php

namespace App\UseCases\Store\Bid;

use App\Services\Store\Bid\GetMyBidService;
use Illuminate\Support\Facades\Log;

class GetMyBidUseCase
{
    public function __construct(
        private GetMyBidService $getMyBidService
    ) {}

    /**
     * 自分の入札を取得
     *
     * @param string $estimateId
     * @param string $storeId
     * @return array|null
     */
    public function execute(string $estimateId, string $storeId): ?array
    {
        $this->validateEstimateId($estimateId);
        $this->validateStoreId($storeId);

        $bid = $this->getMyBidService->execute($estimateId, $storeId);

        return $bid;
    }

    /**
     * 見積もりIDのバリデーション
     *
     * @param string $estimateId
     * @throws \InvalidArgumentException
     */
    private function validateEstimateId(string $estimateId): void
    {
        if (empty($estimateId)) {
            throw new \InvalidArgumentException('見積もりIDが必要です');
        }

        // ULIDの形式チェック（26文字の英数字）
        if (strlen($estimateId) !== 26 || !ctype_alnum($estimateId)) {
            throw new \InvalidArgumentException('見積もりIDの形式が正しくありません');
        }
    }

    /**
     * 店舗IDのバリデーション
     *
     * @param string $storeId
     * @throws \InvalidArgumentException
     */
    private function validateStoreId(string $storeId): void
    {
        if (empty($storeId)) {
            throw new \InvalidArgumentException('店舗IDが必要です');
        }

        // ULIDの形式チェック（26文字の英数字）
        if (strlen($storeId) !== 26 || !ctype_alnum($storeId)) {
            throw new \InvalidArgumentException('店舗IDの形式が正しくありません');
        }
    }
}
