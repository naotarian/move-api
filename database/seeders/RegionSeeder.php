<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regions = [
            ['code' => 1, 'name' => '北海道'],
            ['code' => 2, 'name' => '東北'],
            ['code' => 3, 'name' => '関東'],
            ['code' => 4, 'name' => '中部'],
            ['code' => 5, 'name' => '近畿'],
            ['code' => 6, 'name' => '中国'],
            ['code' => 7, 'name' => '四国'],
            ['code' => 8, 'name' => '九州・沖縄'],
        ];

        foreach ($regions as $region) {
            Region::create($region);
        }

        $this->command->info('地域データを8件作成しました。');
    }
}
