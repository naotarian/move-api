<?php

namespace Database\Seeders;

use App\Models\Estimate;
use App\Models\EmailVerificationToken;
use Illuminate\Database\Seeder;

class EmailVerificationTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estimates = Estimate::all();

        foreach ($estimates as $estimate) {
            if ($estimate->email_verified && $estimate->email_verified_at) {
                // メール認証済みの見積もりには使用済みトークンを作成
                EmailVerificationToken::create([
                    'estimate_id' => $estimate->id,
                    'email' => $estimate->email,
                    'token' => EmailVerificationToken::generateToken(),
                    'status' => EmailVerificationToken::STATUS_USED,
                    'expires_at' => $estimate->email_verified_at->subHours(1), // 認証完了より前に期限切れ
                    'used_at' => $estimate->email_verified_at,
                    'last_sent_at' => $estimate->email_verified_at->subHours(2),
                ]);
            } else {
                // 未認証の見積もりには期限切れまたは有効なトークンを作成（どちらか一つだけ）
                $createPending = rand(1, 5) === 1; // 5件に1件の割合で有効なトークン

                if ($createPending) {
                    // 有効なPENDINGトークンを作成
                    EmailVerificationToken::create([
                        'estimate_id' => $estimate->id,
                        'email' => $estimate->email,
                        'token' => EmailVerificationToken::generateToken(),
                        'status' => EmailVerificationToken::STATUS_PENDING,
                        'expires_at' => now()->addHours(rand(12, 24)),
                        'last_sent_at' => now()->subHours(rand(1, 5)),
                    ]);
                } elseif (rand(0, 1)) {
                    // 期限切れトークンを作成
                    EmailVerificationToken::create([
                        'estimate_id' => $estimate->id,
                        'email' => $estimate->email,
                        'token' => EmailVerificationToken::generateToken(),
                        'status' => EmailVerificationToken::STATUS_EXPIRED,
                        'expires_at' => now()->subHours(rand(1, 24)),
                        'last_sent_at' => now()->subHours(rand(25, 48)),
                    ]);
                }
                // else: トークンを作成しない（まだメール送信されていない状態）
            }
        }
    }
}
