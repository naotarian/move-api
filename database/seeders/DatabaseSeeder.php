<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            PrefectureSeeder::class,
            LuggageCategorySeeder::class,
            LuggageMasterSeeder::class,
            EstimateSeeder::class,
            EmailVerificationTokenSeeder::class, // EstimateSeeder実行後に追加
            SmsVerificationCodeSeeder::class, // EstimateSeeder実行後に追加
            OrganizationSeeder::class, // StoreSeederより先に実行
            StoreSeeder::class,
            AdminSeeder::class,
            BidTestSeeder::class, // 入札テスト用データ
        ]);
    }
}
