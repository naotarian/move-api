<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class ConvertTimestampsToJst extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:convert-to-jst {--dry-run : Show what would be converted without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert existing UTC timestamps to JST (Asia/Tokyo)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->warn('⚠️  This will modify existing database records!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->newLine();
        $this->info('🔄 Converting timestamps from UTC to JST (+9 hours)...');
        $this->newLine();

        // Define tables and their timestamp columns
        $tables = [
            'estimates' => ['created_at', 'updated_at', 'email_verified_at', 'phone_verified_at', 'bid_deadline'],
            'estimate_luggage' => ['created_at', 'updated_at'],
            'estimate_results' => ['created_at', 'updated_at', 'quoted_at', 'expires_at'],
            'moving_from_addresses' => ['created_at', 'updated_at'],
            'moving_to_addresses' => ['created_at', 'updated_at'],
            'email_verification_tokens' => ['created_at', 'updated_at', 'expires_at', 'used_at'],
            'sms_verification_codes' => ['created_at', 'updated_at', 'expires_at', 'used_at'],
            'stores' => ['created_at', 'updated_at'],
            'estimate_bid_rights' => ['created_at', 'updated_at'],
            'bids' => ['created_at', 'updated_at', 'bid_at'],
            'payments' => ['created_at', 'updated_at', 'payment_date'],
            'users' => ['created_at', 'updated_at', 'email_verified_at'],
            'admins' => ['created_at', 'updated_at'],
        ];

        $totalUpdated = 0;

        foreach ($tables as $table => $columns) {
            if (!\Schema::hasTable($table)) {
                $this->warn("   ⚠️  Table '{$table}' does not exist, skipping...");
                continue;
            }

            $this->info("📋 Processing table: {$table}");

            foreach ($columns as $column) {
                if (!\Schema::hasColumn($table, $column)) {
                    $this->warn("     ⚠️  Column '{$column}' does not exist in '{$table}', skipping...");
                    continue;
                }

                try {
                    if ($isDryRun) {
                        // Count records that would be updated
                        $count = \DB::table($table)
                            ->whereNotNull($column)
                            ->count();

                        if ($count > 0) {
                            $this->info("     📊 Would convert {$count} records in column '{$column}'");
                            $totalUpdated += $count;
                        }
                    } else {
                        // Actually update the records
                        $updated = \DB::table($table)
                            ->whereNotNull($column)
                            ->update([
                                $column => \DB::raw("CONVERT_TZ({$column}, '+00:00', '+09:00')")
                            ]);

                        if ($updated > 0) {
                            $this->info("     ✅ Converted {$updated} records in column '{$column}'");
                            $totalUpdated += $updated;
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("     ❌ Error processing column '{$column}': " . $e->getMessage());
                }
            }
        }

        $this->newLine();

        if ($isDryRun) {
            $this->info("🔍 DRY RUN COMPLETED: Would convert {$totalUpdated} timestamp records");
            $this->info("💡 Run without --dry-run to actually perform the conversion");
        } else {
            $this->info("🎉 CONVERSION COMPLETED: {$totalUpdated} timestamp records converted to JST");
            $this->warn("⚠️  Please verify the data and restart your application to ensure timezone settings take effect");
        }

        return 0;
    }
}
