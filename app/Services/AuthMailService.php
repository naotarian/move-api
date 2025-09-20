<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 認証関連メール送信サービス
 * 
 * メールアドレス認証などの認証関連メールを送信する専用サービス
 */
class AuthMailService
{
    public function __construct(
        private MailService $mailService
    ) {}

    /**
     * メール認証リンク付きメール送信
     *
     * @param string $to 送信先メールアドレス
     * @param string $verificationUrl 認証URL
     * @param string $customerName お客様名
     * @return array
     */
    public function sendEmailVerification(string $to, string $verificationUrl, string $customerName = null): array
    {
        $customerName = $customerName ?: 'お客様';
        $companyName = config('app.name', '引越しオークション');

        $subject = "【{$companyName}】メールアドレス認証のお願い";

        $body = $this->buildEmailVerificationBody($customerName, $verificationUrl, $companyName);

        Log::info('Auth Mail Service: Sending email verification', [
            'to' => $to,
            'customer_name' => $customerName
        ]);

        // 開発環境では認証リンクをログに出力（デバッグ用）
        if (config('app.env') === 'local') {
            Log::info('🔗 [DEV] メール認証リンク', [
                'to' => $to,
                'verification_url' => $verificationUrl,
                'message' => '開発環境用: 認証リンクをコピーしてお使いください'
            ]);
        }

        return $this->mailService->sendEmail($to, $subject, $body);
    }

    /**
     * メール認証用のHTMLボディを構築
     *
     * @param string $customerName
     * @param string $verificationUrl
     * @param string $companyName
     * @return string
     */
    private function buildEmailVerificationBody(string $customerName, string $verificationUrl, string $companyName): string
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>メールアドレス認証</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>{$companyName}</h2>
                
                <p>{$customerName} 様</p>
                
                <p>この度は{$companyName}をご利用いただき、ありがとうございます。</p>
                
                <p>お見積もりのお申し込みを受け付けました。<br>
                メールアドレスの認証を完了するため、下記のリンクをクリックしてください。</p>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$verificationUrl}' 
                       style='display: inline-block; padding: 15px 30px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        メールアドレスを認証する
                    </a>
                </div>
                
                <p>※このリンクは24時間有効です。</p>
                <p>※認証完了後、SMS認証画面に進みます。</p>
                
                <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
                
                <p style='font-size: 12px; color: #666;'>
                    このメールは自動送信されています。<br>
                    ご不明な点がございましたら、お気軽にお問い合わせください。<br>
                    {$companyName}
                </p>
            </div>
        </body>
        </html>";
    }
}
