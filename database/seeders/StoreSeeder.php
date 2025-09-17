<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // サンプル店舗1
        Store::create([
            'name' => 'サンプル引越し店',
            'email' => 'store1@moving-auction.local',
            'password' => Hash::make('store123'),
            'phone' => '03-1234-5678',
            'address' => '東京都渋谷区渋谷1-1-1',
            'license_number' => '東京都知事許可 第123456号',
            'status' => Store::STATUS_ACTIVE,
            'is_verified' => true,
        ]);

        // サンプル店舗2
        Store::create([
            'name' => '東京引越しセンター',
            'email' => 'store2@moving-auction.local',
            'password' => Hash::make('store123'),
            'phone' => '03-2345-6789',
            'address' => '東京都新宿区新宿2-2-2',
            'license_number' => '東京都知事許可 第234567号',
            'status' => Store::STATUS_ACTIVE,
            'is_verified' => true,
        ]);

        // サンプル店舗3（未認証）
        Store::create([
            'name' => '未認証引越し店',
            'email' => 'store3@moving-auction.local',
            'password' => Hash::make('store123'),
            'phone' => '03-3456-7890',
            'address' => '東京都品川区品川3-3-3',
            'license_number' => '東京都知事許可 第345678号',
            'status' => Store::STATUS_ACTIVE,
            'is_verified' => false,
        ]);
    }
}
