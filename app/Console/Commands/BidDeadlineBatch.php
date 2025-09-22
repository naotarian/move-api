<?php

namespace App\Console\Commands;

use App\UseCases\Store\BidDeadline\ProcessBidDeadlineUseCase;
use Illuminate\Console\Command;

class BidDeadlineBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bid:process-deadline {--dry-run : 実際の処理を行わずログ出力のみ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '入札期限切れの見積もりを処理し、営業権を付与して通知を送信する';

    public function __construct(
        private ProcessBidDeadlineUseCase $processBidDeadlineUseCase
    ) {
        parent::__construct();
    }

    /**
     * 毎時実行して入札期限切れの見積もりを公開終了にする
     * その時点で入札金額の下限金額が安い上位3社に営業権を付与する
     * 下限金額が同じ場合は、上限金額が安い方を優先とする
     * 両方とも同じ場合は両方に営業権を付与する(4社以上になる場合がある)
     * 営業権を獲得した店舗にはメールを送信する
     * ユーザーには営業権を獲得した業者を通知するメールを送信する
     */
    public function handle(): int
    {
        $this->info('🚀 入札期限バッチ処理を開始します...');
        $this->info('📅 実行日時: ' . now()->format('Y-m-d H:i:s'));

        if ($this->option('dry-run')) {
            $this->warn('⚠️  DRY RUN モード: 実際の処理は行われません');
            return self::SUCCESS;
        }

        $this->info('💡 処理内容:');
        $this->line('  - 実行時刻の00分の入札期限の見積もりを検索');
        $this->line('    例：15:05実行 → 15:00期限の見積もりが対象');
        $this->line('  - 見積もりステータスを「公開終了」に変更');
        $this->line('  - 入札金額順で上位3社に営業権を付与');
        $this->line('  - 営業権獲得店舗・顧客への通知送信');
        $this->line('');

        try {
            // UseCase実行
            $results = $this->processBidDeadlineUseCase->execute();

            // 結果をログ出力
            $this->processBidDeadlineUseCase->logResults($results);

            // コンソール出力
            if ($results['success']) {
                $this->displaySuccessResults($results);
                return self::SUCCESS;
            } else {
                $this->displayErrorResults($results);
                return self::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ バッチ処理中にエラーが発生しました: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * 成功結果を表示
     */
    private function displaySuccessResults(array $results): void
    {
        $this->info('✅ 入札期限バッチ処理が正常に完了しました');

        // 対象期限の表示
        if (isset($results['process_results']['target_deadline'])) {
            $this->info("🎯 対象入札期限: {$results['process_results']['target_deadline']}");
        }

        $this->table(
            ['項目', '件数'],
            [
                ['処理した見積もり', $results['process_results']['processed_estimates'] ?? 0],
                ['付与した営業権', $results['process_results']['granted_business_rights'] ?? 0],
                ['店舗への通知', $results['notification_results']['sent_to_stores'] ?? 0],
                ['顧客への通知', $results['notification_results']['sent_to_customers'] ?? 0],
                ['通知失敗', $results['notification_results']['failed_notifications'] ?? 0],
            ]
        );

        $this->info("⏱️  実行時間: {$results['total_processing_time']}秒");

        if (!empty($results['process_results']['closed_estimates'])) {
            $this->info('📋 処理された見積もりID:');
            foreach ($results['process_results']['closed_estimates'] as $estimateId) {
                $this->line("  - {$estimateId}");
            }
        }
    }

    /**
     * エラー結果を表示
     */
    private function displayErrorResults(array $results): void
    {
        $this->error('❌ 入札期限バッチ処理が失敗しました');
        $this->error("エラー内容: {$results['error']}");
        $this->info("実行時間: {$results['total_processing_time']}秒");
    }
}
