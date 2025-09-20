<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * 汎用メール送信サービス
 * 
 * LaravelのMailファサードを使用したメール送信の基盤クラス
 * 開発環境ではMailHogを使用
 */
class MailService
{
    /**
     * メール送信
     *
     * @param string $to 送信先メールアドレス
     * @param string $subject 件名
     * @param string $body 本文（HTML形式）
     * @param string|null $from 送信元メールアドレス
     * @return array
     */
    public function sendEmail(string $to, string $subject, string $body, string $from = null): array
    {
        try {
            $fromAddress = $from ?: config('mail.from.address');

            Log::info('Mail Service: Sending email', [
                'to' => $to,
                'from' => $fromAddress,
                'subject' => $subject
            ]);

            // 入力値の検証
            $this->validateEmailInputs($to, $subject, $body, $fromAddress);

            // LaravelのMailファサードを使用してメール送信
            Mail::send([], [], function ($message) use ($to, $subject, $body, $fromAddress) {
                $message->to($to)
                    ->from($fromAddress, config('mail.from.name'))
                    ->subject($subject)
                    ->html($body);
            });

            Log::info('Mail Service: Email sent successfully', [
                'to' => $to,
                'mailer' => config('mail.default')
            ]);

            // 開発環境ではMailHog Web UIの情報をログに出力
            if (config('app.env') === 'local') {
                Log::info('📧 [DEV] MailHog Web UI', [
                    'url' => 'http://localhost:8025',
                    'message' => '送信されたメールはMailHog Web UIで確認できます'
                ]);
            }

            return [
                'success' => true,
                'message_id' => 'laravel_mail_' . time(),
                'message' => 'メール送信に成功しました'
            ];
        } catch (\InvalidArgumentException $e) {
            Log::warning('Mail Service: Invalid input', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'メール送信に失敗しました（入力エラー）'
            ];
        } catch (\Exception $e) {
            Log::error('Mail Service: Email sending failed', [
                'to' => $to,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'メール送信に失敗しました'
            ];
        }
    }

    /**
     * 入力値の検証
     *
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $from
     * @throws \InvalidArgumentException
     */
    private function validateEmailInputs(string $to, string $subject, string $body, string $from): void
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('送信先メールアドレスの形式が正しくありません');
        }

        if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('送信元メールアドレスの形式が正しくありません');
        }

        if (empty($subject)) {
            throw new \InvalidArgumentException('件名が入力されていません');
        }

        if (empty($body)) {
            throw new \InvalidArgumentException('本文が入力されていません');
        }

        // 件名の長さチェック
        if (mb_strlen($subject) > 200) {
            throw new \InvalidArgumentException('件名が長すぎます（200文字以内）');
        }
    }
}
