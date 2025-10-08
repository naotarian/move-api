<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Services\Common\RegionPrefectureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RegionController extends Controller
{
    /**
     * 地域一覧を取得
     */
    public function index(): JsonResponse
    {
        try {
            $regions = RegionPrefectureService::getAllRegionsWithPrefectures();

            $data = $regions->map(function ($region) {
                return [
                    'id' => $region->id,
                    'code' => $region->code,
                    'name' => $region->name,
                    'prefectures' => $region->prefectures->map(function ($prefecture) {
                        return [
                            'id' => $prefecture->id,
                            'code' => $prefecture->code,
                            'name' => $prefecture->name,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('地域一覧取得エラー: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '地域一覧の取得に失敗しました',
            ], 500);
        }
    }
}
