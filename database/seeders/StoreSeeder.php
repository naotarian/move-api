<?php

namespace Database\Seeders;

use App\Models\Store;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stores = [
            [
                'name' => 'サンプル引越し店',
                'email' => 'store1@moving-auction.local',
                'phone' => '03-1234-5678',
                'address' => '東京都渋谷区渋谷1-1-1',
                'license_number' => '東京都知事許可 第123456号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '東京引越しセンター',
                'email' => 'store2@moving-auction.local',
                'phone' => '03-2345-6789',
                'address' => '東京都新宿区新宿2-2-2',
                'license_number' => '東京都知事許可 第234567号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '横浜引越しセンター',
                'email' => 'store3@moving-auction.local',
                'phone' => '045-3456-7890',
                'address' => '神奈川県横浜市西区西3-3-3',
                'license_number' => '神奈川県知事許可 第345678号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '川崎引越しセンター',
                'email' => 'store4@moving-auction.local',
                'phone' => '044-4567-8901',
                'address' => '神奈川県川崎市川崎区川崎4-4-4',
                'license_number' => '神奈川県知事許可 第456789号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => false,
            ],
            [
                'name' => '千葉引越しプロ',
                'email' => 'store5@moving-auction.local',
                'phone' => '043-5678-9012',
                'address' => '千葉県千葉市中央区中央5-5-5',
                'license_number' => '千葉県知事許可 第567890号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => 'さいたま引越し専門店',
                'email' => 'store6@moving-auction.local',
                'phone' => '048-6789-0123',
                'address' => '埼玉県さいたま市大宮区大宮6-6-6',
                'license_number' => '埼玉県知事許可 第678901号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '池袋引越しサービス',
                'email' => 'store7@moving-auction.local',
                'phone' => '03-7890-1234',
                'address' => '東京都豊島区池袋7-7-7',
                'license_number' => '東京都知事許可 第789012号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '品川引越しエキスパート',
                'email' => 'store8@moving-auction.local',
                'phone' => '03-8901-2345',
                'address' => '東京都品川区品川8-8-8',
                'license_number' => '東京都知事許可 第890123号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '立川引越し便',
                'email' => 'store9@moving-auction.local',
                'phone' => '042-9012-3456',
                'address' => '東京都立川市立川9-9-9',
                'license_number' => '東京都知事許可 第901234号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => false,
            ],
            [
                'name' => '町田引越しマスター',
                'email' => 'store10@moving-auction.local',
                'phone' => '042-0123-4567',
                'address' => '東京都町田市町田10-10-10',
                'license_number' => '東京都知事許可 第012345号',
                'status' => Store::STATUS_ACTIVE,
                'is_verified' => true,
            ],
        ];

        foreach ($stores as $storeData) {
            Store::create(array_merge($storeData, [
                'password' => Hash::make('store123'),
            ]));
        }
    }
}
