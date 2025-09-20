<?php

namespace App\Services;

use App\Repositories\EstimateRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EstimateService
{
    public function __construct(
        private EstimateRepository $estimateRepository
    ) {}

    /**
     * 見積もり一覧を取得（ページネーション付き）
     */
    public function getPaginatedEstimates(int $perPage = 20, int $page = 1): LengthAwarePaginator
    {
        return $this->estimateRepository->getPaginatedEstimates($perPage, $page);
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
     * 見積もり一覧データを整形
     */
    public function formatEstimatesList(LengthAwarePaginator $estimates): array
    {
        $formattedEstimates = $estimates->map(function ($estimate) {
            return $this->formatEstimateListItem($estimate);
        });

        return [
            'data' => $formattedEstimates,
            'pagination' => [
                'current_page' => $estimates->currentPage(),
                'last_page' => $estimates->lastPage(),
                'per_page' => $estimates->perPage(),
                'total' => $estimates->total(),
                'from' => $estimates->firstItem(),
                'to' => $estimates->lastItem(),
            ],
        ];
    }

    /**
     * 見積もり一覧アイテムを整形
     */
    private function formatEstimateListItem($estimate): array
    {
        return [
            'id' => $estimate->id,
            'moving_from' => [
                'prefecture' => $estimate->movingFromAddress->prefecture,
                'city' => $estimate->movingFromAddress->city,
                'street_address' => $estimate->movingFromAddress->street_address,
                'building_name' => $estimate->movingFromAddress->building_name,
            ],
            'moving_to' => [
                'prefecture' => $estimate->movingToAddress->prefecture,
                'city' => $estimate->movingToAddress->city,
                'street_address' => $estimate->movingToAddress->street_address,
                'building_name' => $estimate->movingToAddress->building_name,
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
            'email_verified' => $estimate->email_verified,
            'phone_verified' => $estimate->phone_verified,
            'verification_completed' => $estimate->email_verified && $estimate->phone_verified,
            'bid_deadline' => $estimate->bid_deadline?->format('Y-m-d H:i:s'),
            'purchase_deadline' => $estimate->getBidRightPurchaseDeadline()?->format('Y-m-d H:i:s'),
            'is_within_purchase_deadline' => $estimate->isWithinPurchaseDeadline(),
            'is_purchase_deadline_expired' => $estimate->isPurchaseDeadlineExpired(),
            'remaining_purchase_minutes' => $estimate->remaining_purchase_minutes,
            'is_within_bid_deadline' => $estimate->isWithinBidDeadline(),
            'is_bid_deadline_expired' => $estimate->isBidDeadlineExpired(),
            'remaining_bid_hours' => $estimate->remaining_bid_hours,
            'created_at' => $estimate->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $estimate->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 見積もり詳細データを整形
     */
    private function formatEstimateData($estimate): array
    {
        \Log::info($estimate->toArray());
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
                'latitude' => $estimate->movingFromAddress->latitude,
                'longitude' => $estimate->movingFromAddress->longitude,
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
                'latitude' => $estimate->movingToAddress->latitude,
                'longitude' => $estimate->movingToAddress->longitude,
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
            'email_verified' => $estimate->email_verified,
            'phone_verified' => $estimate->phone_verified,
            'verification_completed' => $estimate->email_verified && $estimate->phone_verified,
            'bid_deadline' => $estimate->bid_deadline?->format('Y-m-d H:i:s'),
            'purchase_deadline' => $estimate->getBidRightPurchaseDeadline()?->format('Y-m-d H:i:s'),
            'is_within_purchase_deadline' => $estimate->isWithinPurchaseDeadline(),
            'is_purchase_deadline_expired' => $estimate->isPurchaseDeadlineExpired(),
            'remaining_purchase_minutes' => $estimate->remaining_purchase_minutes,
            'is_within_bid_deadline' => $estimate->isWithinBidDeadline(),
            'is_bid_deadline_expired' => $estimate->isBidDeadlineExpired(),
            'remaining_bid_hours' => $estimate->remaining_bid_hours,
            'straight_distance_km' => $estimate->straight_distance_km,
            'luggage_items' => $estimate->luggageItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'luggage' => [
                        'id' => $item->luggage->id,
                        'name' => $item->luggage->name,
                        'category' => $item->luggage->category->name,
                    ],
                ];
            })->toArray(),
            'created_at' => $estimate->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $estimate->updated_at->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 見積もり統計情報を取得
     */
    public function getEstimateStatistics(): array
    {
        return [
            'total' => $this->estimateRepository->getCount(),
            'draft' => $this->estimateRepository->getCountByStatus('draft'),
            'published' => $this->estimateRepository->getCountByStatus('published'),
            'closed' => $this->estimateRepository->getCountByStatus('closed'),
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
