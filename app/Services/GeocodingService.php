<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Services\Common\RegionPrefectureService;

/**
 * Google Geocoding API サービス
 * 
 * 住所を緯度経度に変換するサービス
 */
class GeocodingService
{
    private string $apiKey;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api/geocode/json';

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_api_key');
    }

    /**
     * 住所を緯度経度に変換
     *
     * @param string $address 住所
     * @return array|null ['lat' => float, 'lng' => float] or null
     */
    public function geocode(string $address): ?array
    {
        try {
            Log::info('Geocoding Service: Starting geocoding', [
                'address' => $address
            ]);

            if (empty($this->apiKey)) {
                Log::warning('Geocoding Service: Google Maps API key is not configured');
                return null;
            }

            $response = Http::get($this->baseUrl, [
                'address' => $address,
                'key' => $this->apiKey,
                'language' => 'ja',
                'region' => 'JP'
            ]);

            if (!$response->successful()) {
                Log::error('Geocoding Service: HTTP request failed', [
                    'status' => $response->status(),
                    'address' => $address
                ]);
                return null;
            }

            $data = $response->json();

            if ($data['status'] !== 'OK' || empty($data['results'])) {
                Log::warning('Geocoding Service: No results found', [
                    'status' => $data['status'],
                    'address' => $address
                ]);
                return null;
            }

            $location = $data['results'][0]['geometry']['location'];
            $coordinates = [
                'lat' => (float) $location['lat'],
                'lng' => (float) $location['lng']
            ];

            Log::info('Geocoding Service: Successfully geocoded', [
                'address' => $address,
                'coordinates' => $coordinates
            ]);

            return $coordinates;
        } catch (\Exception $e) {
            Log::error('Geocoding Service: Exception occurred', [
                'address' => $address,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * 2点間の直線距離を計算（Haversine formula）
     *
     * @param array $from ['lat' => float, 'lng' => float]
     * @param array $to ['lat' => float, 'lng' => float]
     * @return int 距離（km）
     */
    public function calculateDistance(array $from, array $to): int
    {
        $earthRadius = 6371; // 地球の半径（km）

        $latFrom = deg2rad($from['lat']);
        $lonFrom = deg2rad($from['lng']);
        $latTo = deg2rad($to['lat']);
        $lonTo = deg2rad($to['lng']);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $distance = $angle * $earthRadius;

        return (int) round($distance);
    }

    /**
     * 住所配列から完全な住所文字列を生成
     *
     * @param array $addressData 住所データ
     * @return string 完全な住所
     */
    public function buildFullAddress(array $addressData): string
    {
        $parts = [];

        if (!empty($addressData['zipcode'])) {
            $parts[] = $addressData['zipcode'];
        }

        if (!empty($addressData['prefecture'])) {
            $parts[] = $addressData['prefecture'];
        }

        if (!empty($addressData['street_address'])) {
            $parts[] = $addressData['street_address'];
        }

        if (!empty($addressData['building_details'])) {
            $parts[] = $addressData['building_details'];
        }

        return implode(' ', $parts);
    }

    /**
     * 住所から地域・都道府県コードを取得
     *
     * @param array $addressData 住所データ
     * @return array ['prefecture_code' => int|null, 'region_code' => int|null]
     */
    public function getRegionAndPrefectureCodes(array $addressData): array
    {
        $fullAddress = $this->buildFullAddress($addressData);
        $result = RegionPrefectureService::getRegionAndPrefecture($fullAddress);

        if (!$result) {
            Log::warning('GeocodingService: Could not determine region/prefecture codes', [
                'address' => $fullAddress
            ]);
            return ['prefecture_code' => null, 'region_code' => null];
        }

        // 都道府県名から都道府県コードを取得
        $prefectureResult = RegionPrefectureService::getModelsByPrefectureName($result['prefecture']);
        if (!$prefectureResult) {
            Log::warning('GeocodingService: Could not find prefecture code', [
                'prefecture' => $result['prefecture']
            ]);
            return ['prefecture_code' => null, 'region_code' => null];
        }

        return [
            'prefecture_code' => $prefectureResult['prefecture']->code,
            'region_code' => $prefectureResult['region']->code
        ];
    }
}
