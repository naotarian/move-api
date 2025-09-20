<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * SMS送信サービス
 * 
 * SNSを使用してSMSを送信する専用サービス
 */
class SmsService
{
    public function __construct(
        private AwsService $awsService
    ) {}

    /**
     * SMS送信
     *
     * @param string $phoneNumber 電話番号 (例: +8190xxxxxxxx)
     * @param string $message メッセージ
     * @return array
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            Log::info('SMS Service: Sending SMS', [
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'message_length' => strlen($message)
            ]);

            // 電話番号の形式チェック
            $this->validatePhoneNumber($phoneNumber);

            // メッセージの長さチェック
            $this->validateMessage($message);

            // SNS クライアントを取得してSMS送信
            $snsClient = $this->awsService->getSnsClient();
            $result = $snsClient->publish([
                'PhoneNumber' => $phoneNumber,
                'Message' => $message,
            ]);

            Log::info('SMS Service: SMS sent successfully', [
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'message_id' => $result['MessageId']
            ]);

            // 開発環境では認証コードをログに出力（デバッグ用）
            if (config('app.env') === 'local') {
                $this->logVerificationCodeFromMessage($message);
            }

            return [
                'success' => true,
                'message_id' => $result['MessageId'],
                'message' => 'SMS送信に成功しました'
            ];
        } catch (\InvalidArgumentException $e) {
            Log::warning('SMS Service: Invalid input', [
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'SMS送信に失敗しました（入力エラー）'
            ];
        } catch (\Exception $e) {
            Log::error('SMS Service: SMS sending failed', [
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'SMS送信に失敗しました'
            ];
        }
    }

    /**
     * 認証コード付きSMS送信
     *
     * @param string $phoneNumber 電話番号
     * @param string $code 認証コード
     * @param string $companyName 会社名（オプション）
     * @return array
     */
    public function sendVerificationCode(string $phoneNumber, string $code, string $companyName = null): array
    {
        $companyName = $companyName ?: config('app.name', '引越しオークション');

        $message = "{$companyName}の認証コードは {$code} です。\n" .
            "このコードを入力して認証を完了してください。\n" .
            "※このコードは10分間有効です。";

        Log::info('SMS Service: Sending verification code', [
            'phone_number' => $this->maskPhoneNumber($phoneNumber),
            'code_length' => strlen($code)
        ]);

        // 開発環境では認証コードをログに出力（デバッグ用）
        if (config('app.env') === 'local') {
            Log::info('🔢 [DEV] SMS認証コード', [
                'phone_number' => $this->maskPhoneNumber($phoneNumber),
                'verification_code' => $code,
                'message' => '開発環境用: SMS認証コードをコピーしてお使いください'
            ]);
        }

        return $this->sendSms($phoneNumber, $message);
    }

    /**
     * 電話番号の形式チェック
     *
     * @param string $phoneNumber
     * @throws \InvalidArgumentException
     */
    private function validatePhoneNumber(string $phoneNumber): void
    {
        if (empty($phoneNumber)) {
            throw new \InvalidArgumentException('電話番号が入力されていません');
        }

        // 日本の電話番号形式（+81から始まる）をチェック
        if (!preg_match('/^\+81[0-9]{10,11}$/', $phoneNumber)) {
            throw new \InvalidArgumentException('電話番号の形式が正しくありません（例: +8190xxxxxxxx）');
        }
    }

    /**
     * メッセージの長さチェック
     *
     * @param string $message
     * @throws \InvalidArgumentException
     */
    private function validateMessage(string $message): void
    {
        if (empty($message)) {
            throw new \InvalidArgumentException('メッセージが入力されていません');
        }

        // SMS の文字数制限（全角70文字、半角160文字程度）
        $maxLength = 160;
        if (mb_strlen($message) > $maxLength) {
            throw new \InvalidArgumentException("メッセージが長すぎます（{$maxLength}文字以内）");
        }
    }

    /**
     * 電話番号をマスク（ログ出力用）
     *
     * @param string $phoneNumber
     * @return string
     */
    private function maskPhoneNumber(string $phoneNumber): string
    {
        if (strlen($phoneNumber) < 8) {
            return str_repeat('*', strlen($phoneNumber));
        }

        // 最初の3文字と最後の4文字を残してマスク
        $start = substr($phoneNumber, 0, 3);
        $end = substr($phoneNumber, -4);
        $middle = str_repeat('*', strlen($phoneNumber) - 7);

        return $start . $middle . $end;
    }

    /**
     * メッセージから認証コードを抽出してログに出力（開発環境用）
     *
     * @param string $message
     */
    private function logVerificationCodeFromMessage(string $message): void
    {
        // 6桁の数字を抽出（認証コードのパターン）
        if (preg_match('/(\d{6})/', $message, $matches)) {
            Log::info('🔢 [DEV] SMS認証コード', [
                'verification_code' => $matches[1],
                'message' => '開発環境用: SMS認証コードをコピーしてお使いください'
            ]);
        }
    }

    /**
     * 電話番号を国際形式に変換
     * 
     * @param string $phoneNumber 日本の電話番号（090-xxxx-xxxx形式等）
     * @return string 国際形式（+8190xxxxxxxx）
     */
    public function formatPhoneNumberToInternational(string $phoneNumber): string
    {
        // 既に国際形式の場合はそのまま返す
        if (str_starts_with($phoneNumber, '+81')) {
            return $phoneNumber;
        }

        // ハイフンやスペースを除去
        $cleanPhone = preg_replace('/[-\s]/', '', $phoneNumber);

        // 先頭の0を削除して+81を付加
        if (str_starts_with($cleanPhone, '0')) {
            return '+81' . substr($cleanPhone, 1);
        }

        // その他の形式の場合はそのまま+81を付加
        return '+81' . $cleanPhone;
    }
}
