<?php

namespace App\Services\Portal;

use App\Repositories\Portal\EstimateRepository;
use App\Models\Estimate;
use App\Services\GeocodingService;
use Illuminate\Support\Facades\Log;

class EstimateService
{
    public function __construct(
        private EstimateRepository $estimateRepository,
        private GeocodingService $geocodingService
    ) {}


    /**
     * Geocoding付きで見積もりを作成
     */
    public function createEstimateWithGeocoding(array $data): Estimate
    {
        Log::info('Portal EstimateService: Starting estimate creation with geocoding', [
            'customer_name' => $data['name'] ?? 'N/A'
        ]);

        // 見積もりデータを作成
        $estimate = $this->estimateRepository->create($data);

        // 住所データの準備（引越し元）
        $fromAddressData = [
            'estimate_id' => $estimate->id,
            'zipcode' => $data['from_zipcode'] ?? '',
            'prefecture' => $data['from_prefecture'] ?? '',
            'street_address' => $data['from_street_address'] ?? '',
            'building_details' => $data['from_building_details'] ?? null,
            'building_type' => $data['from_building_type'] ?? '',
            'room_layout' => $data['from_room_layout'] ?? '',
            'floor' => $data['from_floor'] ?? '',
            'elevator' => $data['from_elevator'] ?? '',
        ];

        // 引越し元住所のGeocodingと作成
        $fromAddress = $this->geocodingService->buildFullAddress($fromAddressData);
        Log::info('Portal EstimateService: Built from address', ['from_address' => $fromAddress]);

        $fromCoords = $this->geocodingService->geocode($fromAddress);
        Log::info('Portal EstimateService: From address geocoded', [
            'from_address' => $fromAddress,
            'from_coords' => $fromCoords
        ]);

        if ($fromCoords) {
            $fromAddressData['latitude'] = $fromCoords['lat'];
            $fromAddressData['longitude'] = $fromCoords['lng'];
        }

        $estimate->movingFromAddress()->create($fromAddressData);

        // 住所データの準備（引越し先）
        $toAddressData = [
            'estimate_id' => $estimate->id,
            'zipcode' => $data['to_zipcode'] ?? '',
            'prefecture' => $data['to_prefecture'] ?? '',
            'street_address' => $data['to_street_address'] ?? '',
            'building_details' => $data['to_building_details'] ?? null,
            'building_type' => $data['to_building_type'] ?? '',
            'room_layout' => $data['to_room_layout'] ?? '',
            'floor' => $data['to_floor'] ?? '',
            'elevator' => $data['to_elevator'] ?? '',
        ];

        // 引越し先住所のGeocodingと作成
        $toAddress = $this->geocodingService->buildFullAddress($toAddressData);
        Log::info('Portal EstimateService: Built to address', ['to_address' => $toAddress]);

        $toCoords = $this->geocodingService->geocode($toAddress);
        Log::info('Portal EstimateService: To address geocoded', [
            'to_address' => $toAddress,
            'to_coords' => $toCoords
        ]);

        if ($toCoords) {
            $toAddressData['latitude'] = $toCoords['lat'];
            $toAddressData['longitude'] = $toCoords['lng'];
        }

        $estimate->movingToAddress()->create($toAddressData);

        // 直線距離の計算と保存
        if ($fromCoords && $toCoords) {
            $distance = $this->geocodingService->calculateDistance($fromCoords, $toCoords);
            $estimate->update(['straight_distance_km' => $distance]);

            Log::info('Portal EstimateService: Distance calculated successfully', [
                'estimate_id' => $estimate->id,
                'distance_km' => $distance
            ]);
        }

        return $estimate;
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
