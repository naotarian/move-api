<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            [
                'name' => 'サンプル引越しグループ',
                'email' => 'org1@moving-auction.local',
                'phone' => '03-1111-1111',
                'address' => '東京都渋谷区渋谷1-1-1 サンプルビル',
                'status' => Organization::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '関東引越しネットワーク',
                'email' => 'org2@moving-auction.local',
                'phone' => '03-2222-2222',
                'address' => '東京都新宿区新宿2-2-2 関東ビル',
                'status' => Organization::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '神奈川引越し連合',
                'email' => 'org3@moving-auction.local',
                'phone' => '045-3333-3333',
                'address' => '神奈川県横浜市西区西3-3-3 神奈川本社ビル',
                'status' => Organization::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => '千葉引越し協会',
                'email' => 'org4@moving-auction.local',
                'phone' => '043-4444-4444',
                'address' => '千葉県千葉市中央区中央4-4-4 千葉センタービル',
                'status' => Organization::STATUS_ACTIVE,
                'is_verified' => true,
            ],
            [
                'name' => 'さいたま引越し組合',
                'email' => 'org5@moving-auction.local',
                'phone' => '048-5555-5555',
                'address' => '埼玉県さいたま市大宮区大宮5-5-5 大宮タワー',
                'status' => Organization::STATUS_ACTIVE,
                'is_verified' => true,
            ],
        ];

        foreach ($organizations as $organizationData) {
            Organization::create(array_merge($organizationData, [
                'password' => Hash::make('org123'),
            ]));
        }
    }
}
