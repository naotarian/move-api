<?php

namespace App\Services;

use Aws\Sns\SnsClient;
use Aws\Ses\SesClient;
use Illuminate\Support\Facades\Log;

/**
 * AWS クライアント管理サービス
 * 
 * AWS SDK クライアントの初期化と管理を担当
 * 実際のSMS/メール送信は専用サービスクラスで実装
 */
class AwsService
{
    private ?SnsClient $snsClient = null;
    private ?SesClient $sesClient = null;

    /**
     * SNS クライアントを取得
     */
    public function getSnsClient(): SnsClient
    {
        if ($this->snsClient === null) {
            $this->snsClient = $this->createSnsClient();
        }

        return $this->snsClient;
    }

    /**
     * SES クライアントを取得
     */
    public function getSesClient(): SesClient
    {
        if ($this->sesClient === null) {
            $this->sesClient = $this->createSesClient();
        }

        return $this->sesClient;
    }

    /**
     * SNS クライアントを作成
     */
    private function createSnsClient(): SnsClient
    {
        $config = $this->getBaseAwsConfig();

        if (config('aws.localstack.enabled')) {
            $config['endpoint'] = config('aws.sns.endpoint');
            Log::debug('AWS Service: Creating SNS client with LocalStack endpoint');
        } else {
            Log::debug('AWS Service: Creating SNS client with AWS endpoint');
        }

        return new SnsClient($config);
    }

    /**
     * SES クライアントを作成
     */
    private function createSesClient(): SesClient
    {
        $config = $this->getBaseAwsConfig();

        if (config('aws.localstack.enabled')) {
            $config['endpoint'] = config('aws.ses.endpoint');
            Log::debug('AWS Service: Creating SES client with LocalStack endpoint');
        } else {
            Log::debug('AWS Service: Creating SES client with AWS endpoint');
        }

        return new SesClient($config);
    }

    /**
     * 基本AWS設定を取得
     */
    private function getBaseAwsConfig(): array
    {
        return [
            'version' => 'latest',
            'region' => config('aws.region'),
            'credentials' => [
                'key' => config('aws.credentials.key'),
                'secret' => config('aws.credentials.secret'),
            ],
        ];
    }


    /**
     * 接続テスト
     */
    public function testConnection(): array
    {
        $results = [];

        // SNS接続テスト
        try {
            $this->getSnsClient()->listTopics();
            $results['sns'] = ['status' => 'connected', 'message' => 'SNS connection successful'];
            Log::info('AWS Service: SNS connection test successful');
        } catch (\Exception $e) {
            $results['sns'] = ['status' => 'failed', 'error' => $e->getMessage()];
            Log::error('AWS Service: SNS connection test failed', ['error' => $e->getMessage()]);
        }

        // SES接続テスト
        try {
            $this->getSesClient()->getIdentityVerificationAttributes(['Identities' => []]);
            $results['ses'] = ['status' => 'connected', 'message' => 'SES connection successful'];
            Log::info('AWS Service: SES connection test successful');
        } catch (\Exception $e) {
            $results['ses'] = ['status' => 'failed', 'error' => $e->getMessage()];
            Log::error('AWS Service: SES connection test failed', ['error' => $e->getMessage()]);
        }

        return $results;
    }
}
