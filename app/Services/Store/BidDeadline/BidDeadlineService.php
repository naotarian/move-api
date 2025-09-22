<?php

namespace App\Services\Store\BidDeadline;

use App\Repositories\Store\BidDeadline\BidDeadlineRepositoryInterface;
use App\Models\Estimate;
use App\Models\Bid;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BidDeadlineService
{
    private const TOP_BUSINESS_RIGHTS_COUNT = 3;

    public function __construct(
        private BidDeadlineRepositoryInterface $bidDeadlineRepository
    ) {}

    /**
     * 期限切れ見積もりを処理
     */
    public function processExpiredEstimates(): array
    {
        $now = \Carbon\Carbon::now();
        // $targetDeadline = $now->copy()->minute(0)->second(0);
        $targetDeadline = new Carbon('2025-09-19 17:00:00');

        Log::info('🔍 入札期限バッチ処理開始', [
            'batch_execution_time' => $now->format('Y-m-d H:i:s'),
            'target_deadline' => $targetDeadline->format('Y-m-d H:i:s'),
            'description' => '実行時刻の00分の入札期限の見積もりを対象とします'
        ]);

        $expiredEstimates = $this->bidDeadlineRepository->getExpiredEstimates();

        Log::info('📋 対象見積もり取得結果', [
            'found_estimates_count' => $expiredEstimates->count(),
            'target_deadline' => $targetDeadline->format('Y-m-d H:i:s')
        ]);

        $results = [
            'processed_estimates' => 0,
            'granted_business_rights' => 0,
            'closed_estimates' => [],
            'target_deadline' => $targetDeadline->format('Y-m-d H:i:s'),
            'batch_execution_time' => $now->format('Y-m-d H:i:s')
        ];

        if ($expiredEstimates->isEmpty()) {
            Log::info('ℹ️  処理対象なし', [
                'message' => '指定された入札期限の見積もりは見つかりませんでした',
                'target_deadline' => $targetDeadline->format('Y-m-d H:i:s')
            ]);
        }

        foreach ($expiredEstimates as $estimate) {
            $this->processEstimate($estimate, $results);
        }

        Log::info('🎯 入札期限バッチ処理完了', array_merge($results, [
            'message' => '対象期限の見積もり処理が完了しました'
        ]));

        return $results;
    }

    /**
     * 個別見積もりを処理
     */
    private function processEstimate(Estimate $estimate, array &$results): void
    {
        Log::info('📋 見積もり処理開始', [
            'estimate_id' => $estimate->id,
            'estimate_name' => $estimate->name,
            'customer_email' => $estimate->email,
            'bid_deadline' => $estimate->bid_deadline?->format('Y-m-d H:i:s')
        ]);

        try {
            // 見積もりを公開終了にする
            $this->bidDeadlineRepository->closeEstimate($estimate);

            // 事前に読み込まれた入札データを使用（N+1問題を回避）
            // estimateBidRights経由で入札データを取得し、ソート
            $bids = $estimate->estimateBidRights
                ->filter(function ($bidRight) {
                    return $bidRight->bid !== null; // 入札が存在するもののみ
                })
                ->map(function ($bidRight) {
                    $bid = $bidRight->bid;
                    // estimateBidRightの情報を入札に追加（後続処理で必要）
                    $bid->estimateBidRight = $bidRight;
                    return $bid;
                })
                ->sortBy([
                    ['bid_amount_min', 'asc'],
                    ['bid_amount_max', 'asc']
                ]);

            if ($bids->isEmpty()) {
                Log::info('❌ 入札なし', [
                    'estimate_id' => $estimate->id,
                    'estimate_name' => $estimate->name,
                    'message' => 'この見積もりには入札がありませんでした'
                ]);
                $results['processed_estimates']++;
                $results['closed_estimates'][] = $estimate->id;
                return;
            }

            Log::info('📊 入札情報', [
                'estimate_id' => $estimate->id,
                'total_bids' => $bids->count(),
                'bids_detail' => $bids->map(function ($bid) {
                    return [
                        'store_name' => $bid->estimateBidRight->store->name ?? '店舗名不明',
                        'bid_range' => number_format($bid->bid_amount_min) . '円 ～ ' . number_format($bid->bid_amount_max) . '円'
                    ];
                })->toArray()
            ]);

            // 営業権を付与
            $businessRights = $this->determineBusinessRights($estimate, $bids);

            if (!empty($businessRights)) {
                $this->bidDeadlineRepository->createBusinessRights($businessRights);
                $results['granted_business_rights'] += count($businessRights);

                Log::info('✅ 営業権付与完了', [
                    'estimate_id' => $estimate->id,
                    'estimate_name' => $estimate->name,
                    'granted_count' => count($businessRights),
                    'message' => count($businessRights) . '社に営業権を付与しました'
                ]);
            } else {
                Log::info('⚠️  営業権付与なし', [
                    'estimate_id' => $estimate->id,
                    'estimate_name' => $estimate->name,
                    'message' => '営業権付与対象の入札がありませんでした'
                ]);
            }

            $results['processed_estimates']++;
            $results['closed_estimates'][] = $estimate->id;
        } catch (\Exception $e) {
            Log::error('BidDeadlineService: Failed to process estimate', [
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 営業権を決定
     * 
     * 営業権付与ルール:
     * 1. 基本は上位3社に営業権を付与
     * 2. 3位と同順位の場合は4社以上になることもある
     * 3. 4位以下で前の順位と金額が異なる場合は営業権付与対象外
     * 
     * 例:
     * 1位: 80,000円 (1社) → 営業権付与
     * 2位: 85,000円 (1社) → 営業権付与  
     * 3位: 90,000円 (2社) → 両社とも営業権付与（計4社）
     * 5位: 95,000円 (1社) → 営業権付与対象外
     */
    private function determineBusinessRights(Estimate $estimate, $bids): array
    {
        Log::info('BidDeadlineService: Determining business rights', [
            'estimate_id' => $estimate->id,
            'bids_count' => $bids->count()
        ]);

        $businessRights = [];
        $currentRanking = 1;
        $previousMinAmount = null;
        $previousMaxAmount = null;

        foreach ($bids as $bid) {
            // 順位を決定
            if ($previousMinAmount !== null && $previousMaxAmount !== null) {
                // 前の入札と金額が異なる場合は順位を更新
                if ($bid->bid_amount_min !== $previousMinAmount || $bid->bid_amount_max !== $previousMaxAmount) {
                    $currentRanking++;
                }
                // 同じ金額の場合は同順位のまま
            }

            // 3位以下で、かつ前の入札と金額が異なる場合は営業権付与対象外
            if (
                $currentRanking > self::TOP_BUSINESS_RIGHTS_COUNT &&
                ($bid->bid_amount_min !== $previousMinAmount || $bid->bid_amount_max !== $previousMaxAmount)
            ) {
                Log::info('🚫 営業権付与対象外', [
                    'store_name' => $bid->estimateBidRight->store->name ?? '店舗名不明',
                    'ranking' => $currentRanking,
                    'bid_range' => number_format($bid->bid_amount_min) . '円 ～ ' . number_format($bid->bid_amount_max) . '円',
                    'reason' => '4位以下のため営業権付与対象外'
                ]);
                break;
            }

            $businessRights[] = [
                'id' => \Illuminate\Support\Str::ulid(),
                'estimate_id' => $estimate->id,
                'store_id' => $bid->estimateBidRight->store_id,
                'store_name' => $bid->estimateBidRight->store->name ?? '店舗名不明',
                'bid_id' => $bid->id,
                'bid_amount_min' => $bid->bid_amount_min,
                'bid_amount_max' => $bid->bid_amount_max,
                'ranking' => $currentRanking,
                'is_notified' => false,
                'granted_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];

            $previousMinAmount = $bid->bid_amount_min;
            $previousMaxAmount = $bid->bid_amount_max;

            $reason = '';
            if ($currentRanking <= 3) {
                $reason = "{$currentRanking}位のため営業権付与";
            } else {
                $reason = "3位と同順位のため営業権付与（4社以上のパターン）";
            }

            Log::info('🏆 営業権付与', [
                'estimate_id' => $estimate->id,
                'estimate_name' => $estimate->name,
                'store_id' => $bid->estimateBidRight->store_id,
                'store_name' => $bid->estimateBidRight->store->name ?? '店舗名不明',
                'ranking' => $currentRanking,
                'bid_amount_min' => number_format($bid->bid_amount_min) . '円',
                'bid_amount_max' => number_format($bid->bid_amount_max) . '円',
                'bid_range' => number_format($bid->bid_amount_min) . '円 ～ ' . number_format($bid->bid_amount_max) . '円',
                'reason' => $reason
            ]);
        }

        return $businessRights;
    }

    /**
     * 通知未完了の営業権を取得
     */
    public function getUnnotifiedBusinessRights(): array
    {
        Log::info('BidDeadlineService: Getting unnotified business rights');

        $businessRights = $this->bidDeadlineRepository->getUnnotifiedBusinessRights();

        return $businessRights->groupBy('estimate_id')->map(function ($rights) {
            return [
                'estimate' => $rights->first()->estimate,
                'rights' => $rights->map(function ($right) {
                    return [
                        'estimate_id' => $right->estimate_id,
                        'store_id' => $right->store_id,
                        'store_name' => $right->store->name ?? '店舗名不明',
                        'ranking' => $right->ranking,
                        'bid_amount_min' => $right->bid_amount_min,
                        'bid_amount_max' => $right->bid_amount_max,
                    ];
                })->toArray()
            ];
        })->toArray();
    }
}
