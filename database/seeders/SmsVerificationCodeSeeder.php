<?php

namespace Database\Seeders;

use App\Models\Estimate;
use App\Models\SmsVerificationCode;
use Illuminate\Database\Seeder;

class SmsVerificationCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estimates = Estimate::all();

        foreach ($estimates as $estimate) {
            if ($estimate->phone_verified && $estimate->phone_verified_at) {
                // 電話認証済みの見積もりには使用済みコードを作成
                SmsVerificationCode::create([
                    'estimate_id' => $estimate->id,
                    'phone' => $estimate->phone,
                    'code' => SmsVerificationCode::generateCode(),
                    'status' => SmsVerificationCode::STATUS_USED,
                    'expires_at' => $estimate->phone_verified_at->subMinutes(1), // 認証完了より前に期限切れ
                    'used_at' => $estimate->phone_verified_at,
                    'last_sent_at' => $estimate->phone_verified_at->subMinutes(10),
                ]);
            } else {
                // 未認証の見積もりには期限切れまたは有効なコードを作成（どちらか一つだけ）
                $createPending = rand(1, 5) === 1; // 5件に1件の割合で有効なコード

                if ($createPending) {
                    // 有効なPENDINGコードを作成
                    SmsVerificationCode::create([
                        'estimate_id' => $estimate->id,
                        'phone' => $estimate->phone,
                        'code' => SmsVerificationCode::generateCode(),
                        'status' => SmsVerificationCode::STATUS_PENDING,
                        'expires_at' => now()->addMinutes(rand(5, 10)),
                        'last_sent_at' => now()->subMinutes(rand(1, 5)),
                    ]);
                } elseif (rand(0, 1)) {
                    // 期限切れコードを作成
                    SmsVerificationCode::create([
                        'estimate_id' => $estimate->id,
                        'phone' => $estimate->phone,
                        'code' => SmsVerificationCode::generateCode(),
                        'status' => SmsVerificationCode::STATUS_EXPIRED,
                        'expires_at' => now()->subMinutes(rand(1, 60)),
                        'last_sent_at' => now()->subMinutes(rand(61, 120)),
                    ]);
                }
                // else: コードを作成しない（まだSMS送信されていない状態）
            }
        }
    }
}
