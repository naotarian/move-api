<?php

namespace App\Services;

use App\Repositories\EstimateRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EstimateService
{
    public function __construct(
        private EstimateRepository $estimateRepository
    ) {}

    /**
     * 見積もりが存在するかどうかを取得
     */
    public function isExists(string $id): bool
    {
        return $this->estimateRepository->isExists($id);
    }

    /**
     * 見積もり一覧を取得（ページネーション付き）
     */
    public function getPaginatedEstimates(string $storeId, int $perPage = 20, int $page = 1, array $filters = []): LengthAwarePaginator
    {
        return $this->estimateRepository->getPaginatedEstimates($storeId, $perPage, $page, $filters);
    }

    /**
     * 見積もり詳細を取得
     */
    public function getEstimateDetail(string $id, ?string $storeId = null): ?array
    {
        $estimate = $this->estimateRepository->findById($id, $storeId);
        \Log::info($estimate->toArray());

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
        $formattedEstimates = collect($estimates->items())->map(function ($estimate) {
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
            'has_bid_right' => isset($estimate->has_bid_right) ? (bool)$estimate->has_bid_right : null,
            'has_bid' => isset($estimate->has_bid) ? (bool)$estimate->has_bid : null,
            'bid_amount_min' => isset($estimate->bid_amount_min) ? (int)$estimate->bid_amount_min : null,
            'bid_amount_max' => isset($estimate->bid_amount_max) ? (int)$estimate->bid_amount_max : null,
            'luggage_by_category' => $this->formatLuggageByCategory($estimate->luggageItems),
            'created_at' => $estimate->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $estimate->updated_at->format('Y-m-d H:i:s'),
        ];
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
                'building_details' => $estimate->movingFromAddress->building_details,
                'building_type' => $this->formatBuildingType($estimate->movingFromAddress->building_type),
                'room_layout' => $estimate->movingFromAddress->room_layout,
                'floor' => $estimate->movingFromAddress->floor,
                'elevator' => $estimate->movingFromAddress->elevator,
                'latitude' => $estimate->movingFromAddress->latitude,
                'longitude' => $estimate->movingFromAddress->longitude,
            ],
            'moving_to' => [
                'zipcode' => $estimate->movingToAddress->zipcode,
                'prefecture' => $estimate->movingToAddress->prefecture,
                'city' => $estimate->movingToAddress->city,
                'street_address' => $estimate->movingToAddress->street_address,
                'building_details' => $estimate->movingToAddress->building_details,
                'building_type' => $this->formatBuildingType($estimate->movingToAddress->building_type),
                'room_layout' => $estimate->movingToAddress->room_layout,
                'floor' => $estimate->movingToAddress->floor,
                'elevator' => $estimate->movingToAddress->elevator,
                'latitude' => $estimate->movingToAddress->latitude,
                'longitude' => $estimate->movingToAddress->longitude,
            ],
            'moving_date_type' => $estimate->moving_date_type,
            'moving_specific_date' => $estimate->moving_specific_date,
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
            'has_bid_right' => isset($estimate->has_bid_right) ? (bool)$estimate->has_bid_right : null,
            'has_bid' => isset($estimate->has_bid) ? (bool)$estimate->has_bid : null,
            'bid_amount_min' => isset($estimate->bid_amount_min) ? (int)$estimate->bid_amount_min : null,
            'bid_amount_max' => isset($estimate->bid_amount_max) ? (int)$estimate->bid_amount_max : null,
            'luggage_by_category' => $this->formatLuggageByCategory($estimate->luggageItems),
            'bid_ranking_list' => $estimate->bid_ranking_list,
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
     * 荷物をカテゴリー別にグループ化
     *
     * @param \Illuminate\Database\Eloquent\Collection $luggageItems
     * @return array
     */
    private function formatLuggageByCategory($luggageItems): array
    {
        if (!$luggageItems) {
            return [];
        }

        $groupedLuggage = [];

        foreach ($luggageItems as $item) {
            $categoryName = $item->luggage->category->name ?? 'その他';

            if (!isset($groupedLuggage[$categoryName])) {
                $groupedLuggage[$categoryName] = [];
            }

            $groupedLuggage[$categoryName][] = [
                'name' => $item->luggage->name,
                'quantity' => $item->quantity,
                'sub_label' => $item->luggage->sub_label ?? null,
            ];
        }

        return $groupedLuggage;
    }
}
