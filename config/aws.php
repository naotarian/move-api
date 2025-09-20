<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AWS SDK Configuration
    |--------------------------------------------------------------------------
    |
    | AWS SDK for PHP configuration for LocalStack and production environment
    |
    */

    'credentials' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
    ],

    'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),

    /*
    |--------------------------------------------------------------------------
    | SNS Configuration (SMS送信用)
    |--------------------------------------------------------------------------
    */
    'sns' => [
        'endpoint' => env('AWS_SNS_ENDPOINT_URL'), // LocalStack用、本番環境では null
        'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SES Configuration (メール送信用)
    |--------------------------------------------------------------------------
    */
    'ses' => [
        'endpoint' => env('AWS_SES_ENDPOINT_URL'), // LocalStack用、本番環境では null
        'region' => env('AWS_DEFAULT_REGION', 'ap-northeast-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LocalStack Settings
    |--------------------------------------------------------------------------
    */
    'localstack' => [
        'enabled' => env('APP_ENV') === 'local',
        'endpoint' => env('AWS_SNS_ENDPOINT_URL', 'http://localstack:4566'),
    ],
];
