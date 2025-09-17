<?php

namespace App\UseCases;

use App\Models\Estimate;
use App\Models\EstimateLuggage;
use App\Repositories\Contracts\EstimateRepositoryInterface;
use App\Services\EstimateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreateEstimateUseCase
{
    public function __construct(
        private EstimateRepositoryInterface $estimateRepository,
        private EstimateService $estimateService
    ) {}

    /**
     * 見積もりを作成する
     */
    public function execute(array $data): array
    {
        try {
            return DB::transaction(function () use ($data) {
                // 1. データの検証
                $validationErrors = $this->estimateService->validateEstimateData($data);
                if (!empty($validationErrors)) {
                    return [
                        'success' => false,
                        'errors' => $validationErrors,
                        'message' => 'バリデーションエラーが発生しました',
                    ];
                }

                // 2. データの正規化
                $normalizedData = $this->estimateService->normalizeEstimateData($data);
                $fromAddressData = $this->estimateService->normalizeFromAddressData($data);
                $toAddressData = $this->estimateService->normalizeToAddressData($data);

                // 3. 見積もりの作成
                $estimate = $this->estimateRepository->create($normalizedData);

                // 4. 引越し元住所の作成
                $fromAddressData['estimate_id'] = $estimate->id;
                $estimate->movingFromAddress()->create($fromAddressData);

                // 5. 引越し先住所の作成
                $toAddressData['estimate_id'] = $estimate->id;
                $estimate->movingToAddress()->create($toAddressData);

                // 6. 荷物データの処理
                $luggageItems = [];
                if (!empty($data['luggage_items'])) {
                    $processedLuggage = $this->estimateService->processLuggageData($data['luggage_items']);
                    
                    // 荷物データをデータベースに保存
                    foreach ($processedLuggage as $luggageData) {
                        $estimateLuggage = EstimateLuggage::create([
                            'estimate_id' => $estimate->id,
                            'luggage_id' => $luggageData['luggage_id'],
                            'quantity' => $luggageData['quantity'],
                        ]);
                        
                        $luggageItems[] = [
                            'id' => $estimateLuggage->id,
                            'luggage_id' => $luggageData['luggage_id'],
                            'luggage_name' => $luggageData['luggage_name'],
                            'luggage_sub_label' => $luggageData['luggage_sub_label'],
                            'category_name' => $luggageData['category_name'],
                            'quantity' => $luggageData['quantity'],
                        ];
                    }
                }

                // 7. 統計情報の計算
                $stats = $this->estimateService->calculateEstimateStats($luggageItems);

                // 8. ログの記録
                Log::info('Estimate created successfully', [
                    'estimate_id' => $estimate->id,
                    'customer_name' => $estimate->name,
                    'total_items' => $stats['total_items'],
                    'total_quantity' => $stats['total_quantity'],
                ]);

                return [
                    'success' => true,
                    'data' => [
                        'estimate' => $estimate,
                        'luggage_items' => $luggageItems,
                        'stats' => $stats,
                    ],
                    'message' => '見積もりが正常に作成されました',
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to create estimate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'message' => '見積もりの作成に失敗しました',
                'error' => config('app.debug') ? $e->getMessage() : 'システムエラーが発生しました',
            ];
        }
    }
}
