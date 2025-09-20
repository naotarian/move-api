<?php

namespace App\Console\Commands;

use App\Services\AwsService;
use App\Services\SmsService;
use App\Services\AuthMailService;
use Illuminate\Console\Command;

class TestAwsConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aws:test-connection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test AWS services connection (LocalStack/Production)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testing AWS services connection...');
        $this->newLine();

        $awsService = new AwsService();
        $results = $awsService->testConnection();

        // SNS結果表示
        $this->info('📱 SNS (SMS) Service:');
        if ($results['sns']['status'] === 'connected') {
            $this->info('   ✅ ' . $results['sns']['message']);
        } else {
            $this->error('   ❌ Connection failed: ' . $results['sns']['error']);
        }

        $this->newLine();

        // SES結果表示
        $this->info('📧 SES (Email) Service:');
        if ($results['ses']['status'] === 'connected') {
            $this->info('   ✅ ' . $results['ses']['message']);
        } else {
            $this->error('   ❌ Connection failed: ' . $results['ses']['error']);
        }

        $this->newLine();

        // 環境情報表示
        $this->info('🔧 Environment Information:');
        $this->info('   Environment: ' . config('app.env'));
        $this->info('   LocalStack enabled: ' . (config('aws.localstack.enabled') ? 'Yes' : 'No'));
        $this->info('   AWS Region: ' . config('aws.region'));
        $this->info('   SNS Endpoint: ' . (config('aws.sns.endpoint') ?: 'Default AWS'));
        $this->info('   SES Endpoint: ' . (config('aws.ses.endpoint') ?: 'Default AWS'));

        $this->newLine();

        // テストSMS送信（オプション）
        if ($this->confirm('Would you like to test SMS sending? (LocalStack only)', false)) {
            $smsService = new SmsService($awsService);
            $this->testSms($smsService);
        }

        // テストメール送信（オプション）
        if ($this->confirm('Would you like to test email sending? (MailHog)', false)) {
            $authMailService = app(AuthMailService::class);
            $this->testEmail($authMailService);
        }

        $this->info('🎉 AWS connection test completed!');
    }

    /**
     * SMS送信テスト
     */
    private function testSms(SmsService $smsService): void
    {
        $phoneNumber = $this->ask('Enter phone number (format: +8190xxxxxxxx)', '+8190xxxxxxxx');

        if ($this->confirm('Send verification code SMS?', true)) {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $this->info('📱 Sending verification code SMS...');
            $result = $smsService->sendVerificationCode($phoneNumber, $code);
        } else {
            $message = $this->ask('Enter SMS message', 'Test SMS from LocalStack');
            $this->info('📱 Sending custom SMS...');
            $result = $smsService->sendSms($phoneNumber, $message);
        }

        if ($result['success']) {
            $this->info('   ✅ ' . $result['message']);
            $this->info('   Message ID: ' . $result['message_id']);
        } else {
            $this->error('   ❌ ' . $result['message']);
            $this->error('   Error: ' . $result['error']);
        }
    }

    /**
     * メール送信テスト
     */
    private function testEmail(AuthMailService $authMailService): void
    {
        $to = $this->ask('Enter recipient email address', 'test@example.com');

        if ($this->confirm('Send email verification?', true)) {
            $verificationUrl = 'http://localhost:3000/verify-email?token=test123';
            $customerName = $this->ask('Enter customer name', 'テスト太郎');

            $this->info('📧 Sending email verification...');
            $result = $authMailService->sendEmailVerification($to, $verificationUrl, $customerName);
        } else {
            $subject = $this->ask('Enter email subject', 'Test Email from LocalStack');
            $body = $this->ask('Enter email body', '<h1>Hello from LocalStack!</h1><p>This is a test email.</p>');

            $this->info('📧 Sending custom email...');
            $mailService = app(\App\Services\MailService::class);
            $result = $mailService->sendEmail($to, $subject, $body);
        }

        if ($result['success']) {
            $this->info('   ✅ ' . $result['message']);
            $this->info('   Message ID: ' . $result['message_id']);
        } else {
            $this->error('   ❌ ' . $result['message']);
            $this->error('   Error: ' . $result['error']);
        }
    }
}
