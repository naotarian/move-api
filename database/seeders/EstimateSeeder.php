<?php

namespace Database\Seeders;

use App\Models\Estimate;
use App\Models\EstimateLuggage;
use App\Models\EstimateResult;
use App\Models\LuggageMaster;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EstimateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 入札テスト用見積もりデータを8件作成中...');

        // 8件のテスト用見積もりデータを作成（入札テストパターンに対応）
        $testPatterns = [
            '3社が入札 (1~3位)',
            '4社が入札 (1~4位) ※4位は営業権なし',
            '1社のみ入札 (1位)',
            '2社が入札 (1~2位)',
            '入札なし',
            '4社が入札、3位が同率 (1~3位、計4社に営業権)',
            '5社が入札、3位が同率で2社 (1~4位、計4社に営業権)',
            '4社が入札、3位と4位の下限同じで上限違い (1~3位、計3社に営業権)',
        ];

        for ($i = 1; $i <= 8; $i++) {
            $this->command->info("📝 見積もり {$i}: {$testPatterns[$i - 1]}");

            $basicData = [
                'name' => "テスト太郎{$i}",
                'name_furigana' => "テストタロウ{$i}",
                'phone' => '090-1234-567' . $i,
                'email' => "user{$i}@example.com",
                'people_count' => rand(1, 4),
                'moving_date_type' => 'decided',
                'moving_specific_date' => Carbon::now()->addDays(rand(7, 30)),
                'work_start_time_type' => 'anytime',
                'work_start_time' => null,
                'other_luggage' => 'テスト用その他荷物',
                'status' => 'published', // 全て公開中
                // 認証フラグ（全て認証済み）
                'email_verified' => true,
                'email_verified_at' => Carbon::now()->subHours(rand(25, 48)),
                'phone_verified' => true,
                'phone_verified_at' => Carbon::now()->subHours(rand(25, 48)),
                // 入札期限を固定時刻に設定（バッチテスト用）
                'bid_deadline' => Carbon::now()->subDays(1)->hour(17)->minute(0)->second(0),
            ];

            $fromAddressData = [
                'zipcode' => '100-0001',
                'prefecture' => '東京都',
                'street_address' => "千代田区千代田{$i}-1-1",
                'building_details' => "テストマンション{$i}01",
                'building_type' => 'mansion',
                'room_layout' => '2LDK',
                'floor' => rand(1, 10),
                'elevator' => rand(0, 1) ? 'yes' : 'no',
                'latitude' => 35.6762 + (rand(-100, 100) / 10000), // 東京周辺
                'longitude' => 139.6503 + (rand(-100, 100) / 10000),
            ];

            $toAddressData = [
                'zipcode' => '150-0001',
                'prefecture' => '東京都',
                'street_address' => "渋谷区神宮前{$i}-1-1",
                'building_details' => "新居マンション{$i}01",
                'building_type' => 'mansion',
                'room_layout' => '3LDK',
                'floor' => rand(1, 10),
                'elevator' => rand(0, 1) ? 'yes' : 'no',
                'latitude' => 35.6627 + (rand(-100, 100) / 10000), // 渋谷周辺
                'longitude' => 139.7039 + (rand(-100, 100) / 10000),
            ];

            // 見積もりを作成
            $estimate = Estimate::create($basicData);

            // 引越し元住所を作成
            $fromAddressData['estimate_id'] = $estimate->id;
            $estimate->movingFromAddress()->create($fromAddressData);

            // 引越し先住所を作成
            $toAddressData['estimate_id'] = $estimate->id;
            $estimate->movingToAddress()->create($toAddressData);

            // 直線距離を計算して保存
            $estimate->update([
                'straight_distance_km' => $this->calculateStraightDistance(
                    $fromAddressData['latitude'],
                    $fromAddressData['longitude'],
                    $toAddressData['latitude'],
                    $toAddressData['longitude']
                )
            ]);

            // 最小限の荷物データを追加
            $this->createMinimalLuggageItems($estimate);
        }

        $this->command->info('✅ 入札テスト用見積もりデータの作成が完了しました！');
    }

    /**
     * 最小限の荷物データを作成
     */
    private function createMinimalLuggageItems(Estimate $estimate): void
    {
        // テスト用の最小限の荷物データ
        $luggageMasters = LuggageMaster::limit(3)->get();

        foreach ($luggageMasters as $luggage) {
            EstimateLuggage::create([
                'estimate_id' => $estimate->id,
                'luggage_id' => $luggage->id,
                'quantity' => rand(1, 3),
            ]);
        }
    }

    /**
     * 直線距離を計算（ハーバーサイン公式）
     */
    private function calculateStraightDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // 地球の半径（km）

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }
}
