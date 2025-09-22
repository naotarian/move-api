<?php

namespace App\UseCases\Store\BidDeadline;

use App\Services\Store\BidDeadline\BidDeadlineService;
use App\Services\Store\BidDeadline\BidDeadlineNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBidDeadlineUseCase
{
    public function __construct(
        private BidDeadlineService $bidDeadlineService,
        private BidDeadlineNotificationService $notificationService
    ) {}

    /**
     * 入札期限バッチ処理を実行
     */
    public function execute(): array
    {
        Log::info('ProcessBidDeadlineUseCase: Starting bid deadline batch process');

        $results = [
            'process_results' => [],
            'notification_results' => [],
            'total_processing_time' => 0,
            'success' => false
        ];

        $startTime = microtime(true);

        try {
            DB::beginTransaction();

            // 1. 期限切れ見積もりの処理と営業権付与
            $processResults = $this->bidDeadlineService->processExpiredEstimates();
            $results['process_results'] = $processResults;

            // 2. 通知未完了の営業権を取得
            $unnotifiedBusinessRights = $this->bidDeadlineService->getUnnotifiedBusinessRights();

            // 3. 通知送信
            if (!empty($unnotifiedBusinessRights)) {
                $notificationResults = $this->notificationService->sendBusinessRightNotifications($unnotifiedBusinessRights);
                $results['notification_results'] = $notificationResults;
            } else {
                $results['notification_results'] = [
                    'sent_to_stores' => 0,
                    'sent_to_customers' => 0,
                    'failed_notifications' => 0
                ];
                Log::info('ProcessBidDeadlineUseCase: No unnotified business rights found');
            }

            DB::commit();
            $results['success'] = true;

            Log::info('ProcessBidDeadlineUseCase: Bid deadline batch process completed successfully', $results);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('ProcessBidDeadlineUseCase: Bid deadline batch process failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $results['error'] = $e->getMessage();
            $results['success'] = false;
        }

        $endTime = microtime(true);
        $results['total_processing_time'] = round($endTime - $startTime, 2);

        return $results;
    }

    /**
     * バッチ処理の実行結果をログ出力
     */
    public function logResults(array $results): void
    {
        if ($results['success']) {
            Log::info('🎉 ===== 入札期限バッチ処理 実行完了 =====', [
                '⏱️  実行時間' => $results['total_processing_time'] . '秒',
                '📋 処理した見積もり数' => $results['process_results']['processed_estimates'] ?? 0,
                '🏆 付与した営業権数' => $results['process_results']['granted_business_rights'] ?? 0,
                '📧 店舗への通知数' => $results['notification_results']['sent_to_stores'] ?? 0,
                '📧 顧客への通知数' => $results['notification_results']['sent_to_customers'] ?? 0,
                '❌ 通知失敗数' => $results['notification_results']['failed_notifications'] ?? 0,
            ]);

            // 処理された見積もりIDの詳細ログ
            if (!empty($results['process_results']['closed_estimates'])) {
                Log::info('📝 処理完了した見積もりID一覧', [
                    'estimate_ids' => $results['process_results']['closed_estimates']
                ]);
            }

            Log::info('✅ ===== バッチ処理正常終了 =====');
        } else {
            Log::error('💥 ===== 入札期限バッチ処理 実行失敗 =====', [
                '❌ エラー内容' => $results['error'] ?? '不明なエラー',
                '⏱️  実行時間' => $results['total_processing_time'] . '秒',
            ]);
        }
    }
}
