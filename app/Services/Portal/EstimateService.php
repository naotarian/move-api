<?php

namespace App\Services\Portal;

use App\Repositories\Portal\EstimateRepository;
use App\Models\Estimate;
use App\Models\MovingFromAddress;
use App\Models\MovingToAddress;
use App\Models\EstimateLuggage;

class EstimateService
{
    public function __construct(
        private EstimateRepository $estimateRepository
    ) {}

    /**
     * 見積もりを作成
     */
    public function createEstimate(array $data): array
    {
        // 見積もりデータを作成
        $estimate = $this->estimateRepository->create($data);
        
        // 作成された見積もりの詳細データを取得
        $estimateDetail = $this->getEstimateDetail($estimate->id);
        
        return $estimateDetail;
    }

    /**
     * 見積もり詳細を取得
     */
    public function getEstimateDetail(string $id): ?array
    {
        $estimate = $this->estimateRepository->findById($id);
        
        if (!$estimate) {
            return null;
        }

        return $this->formatEstimateData($estimate);
    }

    /**
     * 見積もり詳細データを整形
     */
    private function formatEstimateData($estimate): array
    {
        return [
            'id' => $estimate->id,
            'customer_name' => $estimate->name,
            'customer_furigana' => $estimate->name_furigana,
            'customer_phone' => $estimate->phone,
            'customer_email' => $estimate->email,
            'moving_from' => [
                'zipcode' => $estimate->movingFromAddress->zipcode,
                'prefecture' => $estimate->movingFromAddress->prefecture,
                'city' => $estimate->movingFromAddress->city,
                'street_address' => $estimate->movingFromAddress->street_address,
                'building_name' => $estimate->movingFromAddress->building_details,
                'building_type' => $this->formatBuildingType($estimate->movingFromAddress->building_type),
                'floor_plan' => $estimate->movingFromAddress->room_layout,
                'floor_number' => $estimate->movingFromAddress->floor,
                'has_elevator' => $this->formatElevator($estimate->movingFromAddress->elevator),
            ],
            'moving_to' => [
                'zipcode' => $estimate->movingToAddress->zipcode,
                'prefecture' => $estimate->movingToAddress->prefecture,
                'city' => $estimate->movingToAddress->city,
                'street_address' => $estimate->movingToAddress->street_address,
                'building_name' => $estimate->movingToAddress->building_details,
                'building_type' => $this->formatBuildingType($estimate->movingToAddress->building_type),
                'floor_plan' => $estimate->movingToAddress->room_layout,
                'floor_number' => $estimate->movingToAddress->floor,
                'has_elevator' => $this->formatElevator($estimate->movingToAddress->elevator),
            ],
            'moving_date_type' => $estimate->moving_date_type,
            'moving_date' => $estimate->moving_specific_date,
            'moving_year_month' => $estimate->moving_year_month,
            'moving_period' => $estimate->moving_period,
            'people_count' => $estimate->people_count,
            'work_start_time_type' => $estimate->work_start_time_type,
            'work_start_time' => $estimate->work_start_time,
            'other_luggage' => $estimate->other_luggage,
            'status' => $estimate->status,
            'created_at' => $estimate->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $estimate->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 建物タイプを日本語に変換
     */
    private function formatBuildingType(string $buildingType): string
    {
        return match ($buildingType) {
            'mansion' => 'マンション',
            'apartment' => 'アパート',
            'house' => '戸建て',
            'other' => 'その他',
            default => $buildingType,
        };
    }

    /**
     * エレベーターを日本語に変換
     */
    private function formatElevator(string $elevator): string
    {
        return match ($elevator) {
            'yes' => 'あり',
            'no' => 'なし',
            default => $elevator,
        };
    }
}
