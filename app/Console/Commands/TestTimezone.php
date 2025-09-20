<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class TestTimezone extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'timezone:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test timezone settings for PHP, Laravel, and MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🕐 Testing Timezone Settings...');
        $this->newLine();

        // PHP timezone
        $phpTimezone = date_default_timezone_get();
        $this->info("📍 PHP Timezone: {$phpTimezone}");

        // Laravel config timezone
        $laravelTimezone = config('app.timezone');
        $this->info("🚀 Laravel Config Timezone: {$laravelTimezone}");

        // Current time in different formats
        $phpTime = date('Y-m-d H:i:s T');
        $carbonTime = Carbon::now()->format('Y-m-d H:i:s T');
        $carbonJst = Carbon::now('Asia/Tokyo')->format('Y-m-d H:i:s T');

        $this->newLine();
        $this->info("⏰ Current Times:");
        $this->info("   PHP date():           {$phpTime}");
        $this->info("   Carbon::now():        {$carbonTime}");
        $this->info("   Carbon Asia/Tokyo:    {$carbonJst}");

        // Database timezone test
        $this->newLine();
        $this->info("🗄️  Database Timezone Test:");

        try {
            $dbTime = \DB::selectOne('SELECT NOW() as current_time, @@session.time_zone as timezone');
            $this->info("   MySQL NOW():          {$dbTime->current_time}");
            $this->info("   MySQL Timezone:       {$dbTime->timezone}");
        } catch (\Exception $e) {
            $this->error("   Database connection failed: " . $e->getMessage());
        }

        // Test record creation
        $this->newLine();
        $this->info("📝 Testing Record Creation:");

        try {
            // Create a test estimate to see the timestamp
            $estimate = \App\Models\Estimate::create([
                'name' => 'Timezone Test User',
                'name_furigana' => 'タイムゾーンテストユーザー',
                'phone' => '+819012345678',
                'email' => 'timezone-test@example.com',
                'people_count' => 1,
                'moving_date_type' => 'undecided',
                'work_start_time_type' => 'anytime',
                'status' => 'draft',
            ]);

            $this->info("   Created test estimate with ID: {$estimate->id}");
            $this->info("   created_at (raw):     {$estimate->getRawOriginal('created_at')}");
            $this->info("   created_at (Carbon):  {$estimate->created_at->format('Y-m-d H:i:s T')}");
            $this->info("   created_at (JST):     {$estimate->created_at->setTimezone('Asia/Tokyo')->format('Y-m-d H:i:s T')}");

            // Clean up test data
            $estimate->delete();
            $this->info("   ✅ Test estimate deleted");
        } catch (\Exception $e) {
            $this->error("   ❌ Failed to create test record: " . $e->getMessage());
        }

        $this->newLine();
        $this->info('🎉 Timezone test completed!');

        return 0;
    }
}
