<?php

namespace App\UseCases\Estimate;

use App\Services\EstimateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GetEstimatesListUseCase
{
    public function __construct(
        private EstimateService $estimateService
    ) {}

    /**
     * 見積もり一覧を取得する
     */
    public function execute(string $storeId, int $perPage = 20, int $page = 1, array $filters = []): array
    {
        try {
            // トランザクション内でデータを取得
            return DB::transaction(function () use ($storeId, $perPage, $page, $filters) {
                // 見積もり一覧を取得
                $estimates = $this->estimateService->getPaginatedEstimates($storeId, $perPage, $page, $filters);

                // データを整形して返す
                return $this->estimateService->formatEstimatesList($estimates);
            });
        } catch (\Exception $e) {
            Log::error('見積もり一覧取得エラー: ' . $e->getMessage(), [
                'per_page' => $perPage,
                'page' => $page,
                'filters' => $filters,
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('見積もり一覧の取得に失敗しました');
        }
    }
}
