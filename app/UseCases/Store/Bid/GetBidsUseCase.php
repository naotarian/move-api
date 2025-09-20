<?php

namespace App\UseCases\Store\Bid;

use App\Services\Store\Bid\GetBidsService;
use Illuminate\Support\Facades\Log;

class GetBidsUseCase
{
    public function __construct(
        private GetBidsService $getBidsService
    ) {}

    /**
     * 指定された見積もりの入札一覧を取得
     *
     * @param string $estimateId
     * @return array
     */
    public function execute(string $estimateId): array
    {
        $this->validateEstimateId($estimateId);

        $bids = $this->getBidsService->execute($estimateId);

        return $bids;
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
}
