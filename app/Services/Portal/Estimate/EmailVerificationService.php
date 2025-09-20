<?php

namespace App\Services\Portal\Estimate;

use App\Models\Estimate;
use App\Models\EmailVerificationToken;
use App\Services\AuthMailService;
use Illuminate\Support\Facades\Log;

class EmailVerificationService
{
    private AuthMailService $authMailService;

    public function __construct(AuthMailService $authMailService)
    {
        $this->authMailService = $authMailService;
    }

    /**
     * メールアドレス認証メールを送信
     */
    public function sendEmailVerification(Estimate $estimate): array
    {
        try {
            // 認証トークンを作成
            $verificationToken = EmailVerificationToken::createForEstimate(
                $estimate->id,
                $estimate->email
            );

            // 認証リンクを生成（Laravel APIエンドポイントに直接リンク）
            $verificationUrl = config('app.api_url') . '/api/verification/verify-email?token=' . $verificationToken->token;

            // メール送信
            $emailResult = $this->authMailService->sendEmailVerification(
                $estimate->email,
                $verificationUrl,
                $estimate->name
            );

            // 送信結果をトークンに記録
            $verificationToken->update([
                'send_result' => $emailResult,
                'last_sent_at' => now()
            ]);

            if ($emailResult['success']) {
                Log::info('EmailVerificationService: Email verification sent successfully', [
                    'estimate_id' => $estimate->id,
                    'token_id' => $verificationToken->id,
                    'message_id' => $emailResult['message_id'] ?? 'N/A'
                ]);

                return [
                    'success' => true,
                    'message' => 'メール認証を送信しました',
                    'token_id' => $verificationToken->id,
                    'expires_at' => $verificationToken->expires_at,
                    'email_result' => $emailResult
                ];
            } else {
                Log::error('EmailVerificationService: Failed to send email verification', [
                    'estimate_id' => $estimate->id,
                    'token_id' => $verificationToken->id,
                    'error' => $emailResult['message'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'message' => 'メール認証の送信に失敗しました',
                    'error' => $emailResult['message'] ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            Log::error('EmailVerificationService: Email verification failed', [
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'メール認証の送信に失敗しました',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 認証トークンを検証してメール認証を完了
     */
    public function verifyEmailToken(string $token): array
    {
        try {
            Log::info('EmailVerificationService: Starting token verification', [
                'token' => substr($token, 0, 10) . '...'
            ]);

            // トークンでメール認証トークンを検索
            $verificationToken = EmailVerificationToken::findValidToken($token);

            if (!$verificationToken) {
                Log::warning('EmailVerificationService: Invalid or expired token', [
                    'token' => substr($token, 0, 10) . '...'
                ]);

                return [
                    'success' => false,
                    'message' => '無効または期限切れの認証トークンです',
                    'error_code' => 'INVALID_TOKEN'
                ];
            }

            // 見積もりの存在確認
            $estimate = $verificationToken->estimate;
            if (!$estimate) {
                Log::error('EmailVerificationService: Estimate not found', [
                    'token_id' => $verificationToken->id,
                    'estimate_id' => $verificationToken->estimate_id
                ]);

                return [
                    'success' => false,
                    'message' => '見積もりが見つかりません',
                    'error_code' => 'ESTIMATE_NOT_FOUND'
                ];
            }

            // 既に認証済みの場合
            if ($estimate->email_verified) {
                Log::info('EmailVerificationService: Email already verified', [
                    'estimate_id' => $estimate->id,
                    'email' => $estimate->email
                ]);

                // トークンを使用済みにする
                $verificationToken->markAsUsed();

                return [
                    'success' => true,
                    'message' => 'このメールアドレスは既に認証済みです',
                    'already_verified' => true,
                    'estimate_id' => $estimate->id
                ];
            }

            // メール認証を完了
            $estimate->markEmailAsVerified();
            $verificationToken->markAsUsed();

            Log::info('EmailVerificationService: Email verification completed', [
                'estimate_id' => $estimate->id,
                'email' => $estimate->email,
                'token_id' => $verificationToken->id
            ]);

            return [
                'success' => true,
                'message' => 'メールアドレスの認証が完了しました',
                'estimate_id' => $estimate->id,
                'email' => $estimate->email
            ];
        } catch (\Exception $e) {
            Log::error('EmailVerificationService: Token verification failed', [
                'token' => substr($token, 0, 10) . '...',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'メール認証中にエラーが発生しました',
                'error' => $e->getMessage()
            ];
        }
    }
}
