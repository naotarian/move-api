<?php

namespace App\Repositories\Portal;

use App\Models\Estimate;
use App\Models\EstimateLuggage;
use Illuminate\Support\Facades\DB;

class EstimateRepository
{
    /**
     * 見積もりを作成
     */
    public function create(array $data): Estimate
    {
        return DB::transaction(function () use ($data) {
            // 見積もりを作成
            $estimate = Estimate::create([
                'name' => $data['name'],
                'name_furigana' => $data['name_furigana'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'moving_date_type' => $data['moving_date_type'],
                'moving_specific_date' => $data['moving_date_type'] === 'decided' ? $data['moving_specific_date'] : null,
                'moving_year_month' => $data['moving_date_type'] === 'undecided' ? $data['moving_year_month'] : null,
                'moving_period' => $data['moving_date_type'] === 'undecided' ? $data['moving_period'] : null,
                'people_count' => $data['people_count'],
                'work_start_time_type' => $data['work_start_time_type'],
                'work_start_time' => $data['work_start_time_type'] === 'specific' ? $data['work_start_time'] : null,
                'other_luggage' => $data['other_luggage'] ?? null,
                'status' => Estimate::STATUS_PUBLISHED, // フロントエンドから作成された見積もりは公開状態
            ]);

            // 住所データはEstimateServiceでGeocoding付きで作成される

            // 荷物情報を作成
            if (isset($data['luggage_items']) && is_array($data['luggage_items'])) {
                foreach ($data['luggage_items'] as $luggageItem) {
                    EstimateLuggage::create([
                        'estimate_id' => $estimate->id,
                        'luggage_id' => $luggageItem['id'],
                        'quantity' => $luggageItem['quantity'],
                    ]);
                }
            }

            return $estimate;
        });
    }

    /**
     * IDで見積もりを取得
     */
    public function findById(string $id): ?Estimate
    {
        return Estimate::with([
            'movingFromAddress',
            'movingToAddress',
            'luggageItems.luggage'
        ])->find($id);
    }
}
