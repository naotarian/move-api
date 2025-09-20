<?php

namespace App\UseCases\Portal\Estimate;

use App\Services\Portal\EstimateService;
use App\Services\Portal\Estimate\EmailVerificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateEstimateUseCase
{
    public function __construct(
        private EstimateService $estimateService,
        private EmailVerificationService $emailVerificationService
    ) {}

    /**
     * 見積もりを作成する
     */
    public function execute(array $data): array
    {
        try {
            // トランザクション内でデータを作成
            return DB::transaction(function () use ($data) {
                \Log::info('CreateEstimateUseCase: Starting estimate creation', [
                    'customer_name' => $data['name'] ?? 'N/A',
                    'email' => $data['email'] ?? 'N/A'
                ]);

                // 見積もりを作成（Geocoding処理を含む）
                $estimate = $this->estimateService->createEstimateWithGeocoding($data);

                \Log::info('CreateEstimateUseCase: Estimate created successfully', [
                    'estimate_id' => $estimate->id,
                    'customer_name' => $estimate->name
                ]);

                // メール認証を送信（非同期処理ではなく同期処理）
                $emailResult = $this->emailVerificationService->sendEmailVerification($estimate);

                \Log::info('CreateEstimateUseCase: Email verification process completed', [
                    'estimate_id' => $estimate->id,
                    'email_success' => $emailResult['success'] ?? false
                ]);

                return [
                    'success' => true,
                    'message' => '見積もりを作成し、認証メールを送信しました',
                    'data' => [
                        'estimate' => [
                            'id' => $estimate->id,
                            'name' => $estimate->name,
                            'email' => $estimate->email,
                            'phone' => $estimate->phone,
                            'status' => $estimate->status,
                            'email_verified' => $estimate->email_verified,
                            'phone_verified' => $estimate->phone_verified,
                            'created_at' => $estimate->created_at->format('Y-m-d H:i:s'),
                        ],
                        'verification' => [
                            'email_sent' => $emailResult['success'] ?? false,
                            'email_message' => $emailResult['message'] ?? null,
                            'next_step' => 'メールをご確認いただき、認証リンクをクリックしてください'
                        ]
                    ],
                ];
            });
        } catch (\Exception $e) {
            \Log::error('CreateEstimateUseCase: Estimate creation failed', [
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('見積もりの作成に失敗しました: ' . $e->getMessage());
        }
    }
}
