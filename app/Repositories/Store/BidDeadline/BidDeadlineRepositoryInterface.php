<?php

namespace App\Repositories\Store\BidDeadline;

use App\Models\Estimate;
use App\Models\Bid;
use App\Models\BusinessRight;
use Illuminate\Database\Eloquent\Collection;

interface BidDeadlineRepositoryInterface
{
    /**
     * 入札期限が切れた見積もりを取得
     */
    public function getExpiredEstimates(): Collection;

    /**
     * 見積もりのステータスを公開終了に更新
     */
    public function closeEstimate(Estimate $estimate): bool;

    /**
     * 見積もりに対する入札を取得（下限金額順）
     */
    public function getBidsOrderedByAmount(string $estimateId): Collection;

    /**
     * 営業権を作成
     */
    public function createBusinessRight(array $data): BusinessRight;

    /**
     * 営業権を一括作成
     */
    public function createBusinessRights(array $businessRights): bool;

    /**
     * 通知未完了の営業権を取得
     */
    public function getUnnotifiedBusinessRights(): Collection;
}
