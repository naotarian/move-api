<?php

namespace App\UseCases\Estimate;

use App\Services\EstimateService;
use Illuminate\Support\Facades\DB;

class GetEstimateDetailUseCase
{
    public function __construct(
        private EstimateService $estimateService
    ) {}

    /**
     * 見積もり詳細を取得する
     */
    public function execute(string $id): ?array
    {
        try {
            // トランザクション内でデータを取得
            return DB::transaction(function () use ($id) {
                // 見積もり詳細を取得
                $estimateDetail = $this->estimateService->getEstimateDetail($id);
                
                if (!$estimateDetail) {
                    return null;
                }
                
                return $estimateDetail;
            });
        } catch (\Exception $e) {
            \Log::error('見積もり詳細取得エラー: ' . $e->getMessage(), [
                'estimate_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('見積もり詳細の取得に失敗しました');
        }
    }
}
