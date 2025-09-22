<?php

namespace Database\Seeders;

use App\Models\Estimate;
use App\Models\Store;
use App\Models\Payment;
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
            'payments' => 0,
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
                // 1. Payment作成
                $payment = $this->createPayment($estimate, $store);
                $totalCreated['payments']++;

                // 2. EstimateBidRight作成
                $bidRight = $this->createEstimateBidRight($estimate, $store, $payment);
                $totalCreated['bid_rights']++;

                // 3. 指定されたパターンに従ってBid作成（パターンに含まれる店舗のみ）
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
                ['決済 (Payments)', $totalCreated['payments']],
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
     * 決済データを作成
     */
    private function createPayment(Estimate $estimate, Store $store): Payment
    {
        return Payment::create([
            'estimate_id' => $estimate->id,
            'store_id' => $store->id,
            'amount_excluding_tax' => 500, // 税抜き500円
            'amount_including_tax' => 500,  // 税込み500円（税率0%）
            'tax_amount' => 0,
            'tax_rate' => 0,
            'payment_date' => Carbon::now()->subHours(rand(1, 72)),
            'payment_method' => 1, // クレジットカード
            'status' => 1, // 成功
            'provider' => 'stripe',
            'provider_id' => 'pi_test_' . Str::random(24),
            'provider_url' => null,
            'failure_reason' => null,
        ]);
    }

    /**
     * 入札権データを作成
     */
    private function createEstimateBidRight(Estimate $estimate, Store $store, Payment $payment): EstimateBidRight
    {
        return EstimateBidRight::create([
            'estimate_id' => $estimate->id,
            'store_id' => $store->id,
            'status' => 1, // 有効
            'payment_id' => $payment->id,
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
