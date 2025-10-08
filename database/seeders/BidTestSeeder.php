<?php

namespace Database\Seeders;

use App\Models\Estimate;
use App\Models\Store;
use App\Models\EstimateBidRight;
use App\Models\Bid;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BidTestSeeder extends Seeder
{
    /**
     * 入札テスト用のシーダー - 営業権付与パターンを網羅的にテスト
     * 
     * テストパターン:
     * 1. 3社が入札 (1~3位)
     * 2. 4社が入札 (1~4位) ※4位は営業権なし
     * 3. 1社のみ入札 (1位)
     * 4. 2社が入札 (1~2位)
     * 5. 入札なし
     * 6. 4社が入札、3位が同率 (1~3位、計4社に営業権)
     * 7. 5社が入札、3位が同率で2社 (1~4位、計4社に営業権)
     * 8. 4社が入札、3位と4位の下限同じで上限違い (1~3位、計3社に営業権)
     */
    public function run(): void
    {
        $this->command->info('🚀 入札テスト用データを作成中...');

        // 既存のEstimateとStoreを取得
        $estimates = Estimate::where('status', 'published')->take(8)->get();
        $stores = Store::where('status', 'active')->get();

        if ($estimates->count() < 8) {
            $this->command->warn('⚠️  公開中の見積もりが8件未満です。EstimateSeederを先に実行してください。');
            return;
        }

        if ($stores->count() < 10) {
            $this->command->warn('⚠️  アクティブな店舗が10件未満です。StoreSeederを先に実行してください。');
            return;
        }

        $totalCreated = [
            'bid_rights' => 0,
            'bids' => 0,
        ];

        // テストパターンを定義
        $testPatterns = [
            [
                'name' => '3社が入札 (1~3位)',
                'bids' => [
                    ['min' => 80000, 'max' => 100000],  // 1位
                    ['min' => 85000, 'max' => 105000],  // 2位
                    ['min' => 90000, 'max' => 110000],  // 3位
                ],
                'expected_winners' => 3
            ],
            [
                'name' => '4社が入札 (1~4位) ※4位は営業権なし',
                'bids' => [
                    ['min' => 75000, 'max' => 95000],   // 1位
                    ['min' => 80000, 'max' => 100000],  // 2位
                    ['min' => 85000, 'max' => 105000],  // 3位
                    ['min' => 95000, 'max' => 115000],  // 4位（営業権なし）
                ],
                'expected_winners' => 3
            ],
            [
                'name' => '1社のみ入札 (1位)',
                'bids' => [
                    ['min' => 100000, 'max' => 120000], // 1位
                ],
                'expected_winners' => 1
            ],
            [
                'name' => '2社が入札 (1~2位)',
                'bids' => [
                    ['min' => 70000, 'max' => 90000],   // 1位
                    ['min' => 85000, 'max' => 105000],  // 2位
                ],
                'expected_winners' => 2
            ],
            [
                'name' => '入札なし',
                'bids' => [],
                'expected_winners' => 0
            ],
            [
                'name' => '4社が入札、3位が同率 (1~3位、計4社に営業権)',
                'bids' => [
                    ['min' => 70000, 'max' => 90000],   // 1位
                    ['min' => 80000, 'max' => 100000],  // 2位
                    ['min' => 90000, 'max' => 110000],  // 3位（同率1社目）
                    ['min' => 90000, 'max' => 110000],  // 3位（同率2社目）
                ],
                'expected_winners' => 4
            ],
            [
                'name' => '5社が入札、3位が同率で2社 (1~4位、計4社に営業権)',
                'bids' => [
                    ['min' => 65000, 'max' => 85000],   // 1位
                    ['min' => 75000, 'max' => 95000],   // 2位
                    ['min' => 85000, 'max' => 105000],  // 3位（同率1社目）
                    ['min' => 85000, 'max' => 105000],  // 3位（同率2社目）
                    ['min' => 95000, 'max' => 115000],  // 5位（営業権なし）
                ],
                'expected_winners' => 4
            ],
            [
                'name' => '4社が入札、3位と4位の下限同じで上限違い (1~3位、計3社に営業権)',
                'bids' => [
                    ['min' => 70000, 'max' => 90000],   // 1位
                    ['min' => 80000, 'max' => 100000],  // 2位
                    ['min' => 90000, 'max' => 110000],  // 3位（上限安い方が勝ち）
                    ['min' => 90000, 'max' => 115000],  // 4位（上限高いので負け、営業権なし）
                ],
                'expected_winners' => 3
            ],
        ];

        foreach ($estimates as $index => $estimate) {
            $pattern = $testPatterns[$index];
            $this->command->info("📝 見積もり {$estimate->id}: {$pattern['name']}");

            $bidCount = count($pattern['bids']);

            // 全ての店舗に入札権を付与
            foreach ($stores as $storeIndex => $store) {
                // 1. EstimateBidRight作成
                $bidRight = $this->createEstimateBidRight($estimate, $store);
                $totalCreated['bid_rights']++;

                // 2. 指定されたパターンに従ってBid作成（パターンに含まれる店舗のみ）
                if ($storeIndex < $bidCount) {
                    $bidData = $pattern['bids'][$storeIndex];
                    $this->createBid($bidRight, $bidData['min'], $bidData['max']);
                    $totalCreated['bids']++;
                }
            }

            $this->command->line("   入札権付与店舗数: {$stores->count()}社");
            $this->command->line("   実際の入札数: {$bidCount}社");
            $this->command->line("   期待営業権獲得数: {$pattern['expected_winners']}社");
        }

        $this->command->info('✅ 入札テスト用データの作成が完了しました！');
        $this->command->table(
            ['データ種類', '作成数'],
            [
                ['入札権 (EstimateBidRights)', $totalCreated['bid_rights']],
                ['入札 (Bids)', $totalCreated['bids']],
            ]
        );

        $this->command->info('🎯 テストパターン一覧:');
        foreach ($testPatterns as $i => $pattern) {
            $this->command->line(($i + 1) . ". {$pattern['name']} (期待営業権: {$pattern['expected_winners']}社)");
        }
    }

    /**
     * 入札権データを作成
     */
    private function createEstimateBidRight(Estimate $estimate, Store $store): EstimateBidRight
    {
        return EstimateBidRight::create([
            'estimate_id' => $estimate->id,
            'store_id' => $store->id,
            'status' => 1, // 有効
        ]);
    }

    /**
     * 入札データを作成（指定金額）
     */
    private function createBid(EstimateBidRight $bidRight, int $minAmount, int $maxAmount): Bid
    {
        return Bid::create([
            'estimate_bid_right_id' => $bidRight->id,
            'bid_amount_min' => $minAmount,
            'bid_amount_max' => $maxAmount,
            'bid_at' => Carbon::now()->subHours(rand(1, 48)),
        ]);
    }
}
