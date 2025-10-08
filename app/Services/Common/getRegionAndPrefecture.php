<?php

namespace App\Services\Common;

use App\Models\Prefecture;
use App\Models\Region;
use Illuminate\Support\Facades\Cache;

class RegionPrefectureService
{
    /**
     * 住所から地域と都道府県を取得
     *
     * @param string $address 住所文字列
     * @return array|null ['region' => Region, 'prefecture' => Prefecture] または null
     */
    public static function getRegionAndPrefecture(string $address): ?array
    {
        // キャッシュキーを生成
        $cacheKey = 'prefecture_data';

        // 都道府県データをキャッシュから取得（24時間キャッシュ）
        $prefectures = Cache::remember($cacheKey, 60 * 60 * 24, function () {
            return Prefecture::with('region')->orderByCode()->get();
        });

        // 住所に都道府県名が含まれているかチェック
        foreach ($prefectures as $prefecture) {
            if (mb_strpos($address, $prefecture->name) !== false) {
                return [
                    'region' => $prefecture->region,
                    'prefecture' => $prefecture
                ];
            }
        }

        // 見つからない場合はnullを返す
        return null;
    }

    /**
     * 都道府県名から地域と都道府県を取得
     *
     * @param string $prefectureName 都道府県名
     * @return array|null ['region' => Region, 'prefecture' => Prefecture] または null
     */
    public static function getByPrefectureName(string $prefectureName): ?array
    {
        $prefecture = Prefecture::with('region')->byName($prefectureName)->first();

        if (!$prefecture) {
            return null;
        }

        return [
            'region' => $prefecture->region,
            'prefecture' => $prefecture
        ];
    }

    /**
     * 都道府県コードから地域と都道府県を取得
     *
     * @param int $prefectureCode 都道府県コード
     * @return array|null ['region' => Region, 'prefecture' => Prefecture] または null
     */
    public static function getByPrefectureCode(int $prefectureCode): ?array
    {
        $prefecture = Prefecture::with('region')->byCode($prefectureCode)->first();

        if (!$prefecture) {
            return null;
        }

        return [
            'region' => $prefecture->region,
            'prefecture' => $prefecture
        ];
    }

    /**
     * 全ての地域と都道府県を取得
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAllRegionsWithPrefectures()
    {
        return Region::with(['prefectures' => function ($query) {
            $query->orderByCode();
        }])->orderByCode()->get();
    }

    /**
     * 地域別の都道府県一覧を配列で取得
     *
     * @return array
     */
    public static function getRegionPrefectureArray(): array
    {
        $cacheKey = 'region_prefecture_array';

        return Cache::remember($cacheKey, 60 * 60 * 24, function () {
            $regions = Region::with(['prefectures' => function ($query) {
                $query->orderByCode();
            }])->orderByCode()->get();

            $result = [];
            foreach ($regions as $region) {
                $result[$region->name] = $region->prefectures->pluck('name')->toArray();
            }

            return $result;
        });
    }
}
