<?php

namespace App\Repositories\Store\BidDeadline;

use App\Models\Estimate;
use App\Models\Bid;
use App\Models\BusinessRight;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BidDeadlineRepository implements BidDeadlineRepositoryInterface
{
    /**
     * 入札期限が切れた見積もりを取得
     * バッチ実行時刻の直前の00分の入札期限のもののみを対象とする
     */
    public function getExpiredEstimates(): Collection
    {
        $now = Carbon::now();

        // 現在時刻の00分（例：15:05 → 15:00）
        // $targetDeadline = $now->copy()->minute(0)->second(0);
        $targetDeadline = Carbon::now()->subDays(1)->hour(17)->minute(0)->second(0);

        Log::info('BidDeadlineRepository: Getting expired estimates', [
            'current_time' => $now->format('Y-m-d H:i:s'),
            'target_deadline' => $targetDeadline->format('Y-m-d H:i:s')
        ]);

        return Estimate::with([
            'estimateBidRights' => function ($query) {
                $query->where('status', 1) // 入札権ありのみ
                    ->with(['bid', 'store']); // 入札と店舗情報を事前読み込み
            },
            'estimateBidRights.bid' => function ($query) {
                $query->orderBy('bid_amount_min', 'asc')
                    ->orderBy('bid_amount_max', 'asc');
            }
        ])
            ->where('status', Estimate::STATUS_PUBLISHED)
            ->where('bid_deadline', $targetDeadline)
            ->get();
    }

    /**
     * 見積もりのステータスを公開終了に更新
     */
    public function closeEstimate(Estimate $estimate): bool
    {
        Log::info('BidDeadlineRepository: Closing estimate', [
            'estimate_id' => $estimate->id
        ]);

        return $estimate->update([
            'status' => Estimate::STATUS_CLOSED
        ]);
    }

    /**
     * 見積もりに対する入札を取得（下限金額順）
     */
    public function getBidsOrderedByAmount(string $estimateId): Collection
    {
        Log::info('BidDeadlineRepository: Getting bids ordered by amount', [
            'estimate_id' => $estimateId
        ]);

        return Bid::with(['estimateBidRight.store'])
            ->whereHas('estimateBidRight', function ($query) use ($estimateId) {
                $query->where('estimate_id', $estimateId)
                    ->where('status', 1); // 入札権ありのみ
            })
            ->orderBy('bid_amount_min', 'asc')
            ->orderBy('bid_amount_max', 'asc')
            ->get();
    }

    /**
     * 営業権を作成
     */
    public function createBusinessRight(array $data): BusinessRight
    {
        Log::info('BidDeadlineRepository: Creating business right', [
            'data' => $data
        ]);

        return BusinessRight::create($data);
    }

    /**
     * 営業権を一括作成
     */
    public function createBusinessRights(array $businessRights): bool
    {
        Log::info('BidDeadlineRepository: Creating business rights in bulk', [
            'count' => count($businessRights)
        ]);

        try {
            // store_nameはDBに保存不要なので除外
            $dbData = array_map(function ($right) {
                unset($right['store_name']);
                return $right;
            }, $businessRights);

            BusinessRight::insert($dbData);
            return true;
        } catch (\Exception $e) {
            Log::error('BidDeadlineRepository: Failed to create business rights', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 通知未完了の営業権を取得
     */
    public function getUnnotifiedBusinessRights(): Collection
    {
        Log::info('BidDeadlineRepository: Getting unnotified business rights');

        return BusinessRight::with(['estimate', 'store', 'bid'])
            ->where('is_notified', false)
            ->get();
    }
}
