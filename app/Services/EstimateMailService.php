<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * 見積もり関連メール送信サービス
 * 
 * 見積もり受付完了メールなどの見積もり関連メールを送信する専用サービス
 */
class EstimateMailService
{
    public function __construct(
        private MailService $mailService
    ) {}

    /**
     * 見積もり受付完了メール送信
     *
     * @param string $to 送信先メールアドレス
     * @param string $customerName お客様名
     * @param array $estimateData 見積もりデータ
     * @return array
     */
    public function sendEstimateReceived(string $to, string $customerName, array $estimateData): array
    {
        $companyName = config('app.name', '引越しオークション');

        $subject = "【{$companyName}】お見積もりを受け付けました";

        $body = $this->buildEstimateReceivedBody($customerName, $estimateData, $companyName);

        Log::info('Estimate Mail Service: Sending estimate received email', [
            'to' => $to,
            'customer_name' => $customerName,
            'estimate_id' => $estimateData['id'] ?? 'unknown'
        ]);

        return $this->mailService->sendEmail($to, $subject, $body);
    }

    /**
     * 見積もり受付完了用のHTMLボディを構築
     *
     * @param string $customerName
     * @param array $estimateData
     * @param string $companyName
     * @return string
     */
    private function buildEstimateReceivedBody(string $customerName, array $estimateData, string $companyName): string
    {
        $estimateId = $estimateData['id'] ?? '不明';
        $fromAddress = $estimateData['from_address'] ?? '不明';
        $toAddress = $estimateData['to_address'] ?? '不明';
        $moveDate = $estimateData['move_date'] ?? '不明';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>お見積もり受付完了</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2c3e50;'>{$companyName}</h2>
                
                <p>{$customerName} 様</p>
                
                <p>お見積もりのお申し込みを受け付けました。</p>
                
                <div style='background-color: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <h3 style='margin-top: 0; color: #2c3e50;'>お見積もり内容</h3>
                    <p><strong>見積もりID:</strong> {$estimateId}</p>
                    <p><strong>引越し元:</strong> {$fromAddress}</p>
                    <p><strong>引越し先:</strong> {$toAddress}</p>
                    <p><strong>希望日:</strong> {$moveDate}</p>
                </div>
                
                <p>引越し業者からの見積もりをお待ちください。<br>
                見積もりが届き次第、ご連絡いたします。</p>
                
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
