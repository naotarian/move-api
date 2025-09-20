<?php

namespace App\Console\Commands;

use App\Models\Estimate;
use Illuminate\Console\Command;

class CloseExpiredEstimates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'estimates:close-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close estimates that have passed their bid deadline';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🕒 Checking for expired estimates...');

        // 入札期限切れの公開中の見積もりを取得
        $expiredEstimates = Estimate::published()
            ->bidDeadlineExpired()
            ->get();

        if ($expiredEstimates->isEmpty()) {
            $this->info('✅ No expired estimates found.');
            return 0;
        }

        $this->info("📋 Found {$expiredEstimates->count()} expired estimates");

        $closedCount = 0;

        foreach ($expiredEstimates as $estimate) {
            try {
                $estimate->update(['status' => Estimate::STATUS_CLOSED]);
                $closedCount++;

                $this->info("   ✅ Closed estimate ID: {$estimate->id} (deadline: {$estimate->bid_deadline->format('Y-m-d H:i')})");

                \Log::info('CloseExpiredEstimates: Estimate closed', [
                    'estimate_id' => $estimate->id,
                    'bid_deadline' => $estimate->bid_deadline->toDateTimeString(),
                    'closed_at' => now()->toDateTimeString()
                ]);
            } catch (\Exception $e) {
                $this->error("   ❌ Failed to close estimate ID: {$estimate->id} - {$e->getMessage()}");

                \Log::error('CloseExpiredEstimates: Failed to close estimate', [
                    'estimate_id' => $estimate->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->info("🎉 Successfully closed {$closedCount} expired estimates");

        return 0;
    }
}
