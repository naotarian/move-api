<?php

namespace App\UseCases\Portal\Estimate;

use App\Services\Portal\EstimateService;
use Illuminate\Support\Facades\DB;

class CreateEstimateUseCase
{
    public function __construct(
        private EstimateService $estimateService
    ) {}

    /**
     * 見積もりを作成する
     */
    public function execute(array $data): array
    {
        try {
            // トランザクション内でデータを作成
            return DB::transaction(function () use ($data) {
                // 見積もりを作成
                $estimate = $this->estimateService->createEstimate($data);
                
                return [
                    'success' => true,
                    'message' => '見積もりを作成しました',
                    'data' => $estimate,
                ];
            });
        } catch (\Exception $e) {
            \Log::error('見積もり作成エラー: ' . $e->getMessage(), [
                'data' => $data,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('見積もりの作成に失敗しました');
        }
    }
}
