<?php

namespace App\Services\Portal\Estimate;

use App\Models\Estimate;
use App\Models\SmsVerificationCode;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SmsVerificationService
{
    private SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * SMS認証コード送信
     */
    public function sendSmsVerification(Estimate $estimate): array
    {
        Log::info('SmsVerificationService: Starting SMS verification', [
            'estimate_id' => $estimate->id,
            'phone' => $estimate->phone,
            'customer_name' => $estimate->name
        ]);

        try {
            // 認証コードを作成（既存のPENDINGコードは自動的に期限切れになる）
            $verificationCode = SmsVerificationCode::createForEstimate(
                $estimate->id,
                $estimate->phone
            );

            Log::info('SmsVerificationService: Generated verification code', [
                'estimate_id' => $estimate->id,
                'code_id' => $verificationCode->id,
                'code' => $verificationCode->code,
                'expires_at' => $verificationCode->expires_at->format('Y-m-d H:i:s')
            ]);

            // 電話番号を国際形式に変換してSMS送信
            $internationalPhone = $this->smsService->formatPhoneNumberToInternational($estimate->phone);
            $smsResult = $this->smsService->sendVerificationCode(
                $internationalPhone,
                $verificationCode->code
            );

            // 送信結果をコードに記録
            $verificationCode->update([
                'send_result' => $smsResult,
                'last_sent_at' => now()
            ]);

            if ($smsResult['success']) {
                Log::info('SmsVerificationService: SMS verification sent successfully', [
                    'estimate_id' => $estimate->id,
                    'code_id' => $verificationCode->id,
                    'message_id' => $smsResult['message_id'] ?? 'N/A'
                ]);

                return [
                    'success' => true,
                    'message' => 'SMS認証コードを送信しました',
                    'code_id' => $verificationCode->id,
                    'expires_at' => $verificationCode->expires_at,
                    'sms_result' => $smsResult
                ];
            } else {
                Log::error('SmsVerificationService: Failed to send SMS verification', [
                    'estimate_id' => $estimate->id,
                    'code_id' => $verificationCode->id,
                    'error' => $smsResult['message'] ?? 'Unknown error'
                ]);

                return [
                    'success' => false,
                    'message' => 'SMS認証コードの送信に失敗しました',
                    'error' => $smsResult['message'] ?? 'Unknown error'
                ];
            }
        } catch (\Exception $e) {
            Log::error('SmsVerificationService: SMS verification failed', [
                'estimate_id' => $estimate->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'SMS認証コードの送信に失敗しました',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * SMS認証コードを検証して電話認証を完了
     */
    public function verifySmsCode(string $code): array
    {
        try {
            Log::info('SmsVerificationService: Starting code verification', [
                'code' => $code
            ]);

            // コードでSMS認証コードを検索
            $verificationCode = SmsVerificationCode::findValidCode($code);

            if (!$verificationCode) {
                Log::warning('SmsVerificationService: Invalid or expired code', [
                    'code' => $code
                ]);

                return [
                    'success' => false,
                    'message' => '無効または期限切れの認証コードです',
                    'error_code' => 'INVALID_CODE'
                ];
            }

            // 見積もりの存在確認
            $estimate = $verificationCode->estimate;
            if (!$estimate) {
                Log::error('SmsVerificationService: Estimate not found', [
                    'code_id' => $verificationCode->id,
                    'estimate_id' => $verificationCode->estimate_id
                ]);

                return [
                    'success' => false,
                    'message' => '見積もりが見つかりません',
                    'error_code' => 'ESTIMATE_NOT_FOUND'
                ];
            }

            // 既に認証済みの場合
            if ($estimate->phone_verified) {
                Log::info('SmsVerificationService: Phone already verified', [
                    'estimate_id' => $estimate->id,
                    'phone' => $estimate->phone
                ]);

                // コードを使用済みにする
                $verificationCode->markAsUsed();

                return [
                    'success' => true,
                    'message' => 'この電話番号は既に認証済みです',
                    'already_verified' => true,
                    'estimate_id' => $estimate->id
                ];
            }

            // 電話認証を完了
            $estimate->markPhoneAsVerified();
            $verificationCode->markAsUsed();

            Log::info('SmsVerificationService: Phone verification completed', [
                'estimate_id' => $estimate->id,
                'phone' => $estimate->phone,
                'code_id' => $verificationCode->id
            ]);

            return [
                'success' => true,
                'message' => '電話番号の認証が完了しました',
                'estimate_id' => $estimate->id,
                'phone' => $estimate->phone
            ];
        } catch (\Exception $e) {
            Log::error('SmsVerificationService: Code verification failed', [
                'code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'SMS認証中にエラーが発生しました',
                'error' => $e->getMessage()
            ];
        }
    }
}
