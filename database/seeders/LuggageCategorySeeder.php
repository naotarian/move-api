<?php

namespace Database\Seeders;

use App\Models\LuggageCategory;
use Illuminate\Database\Seeder;

class LuggageCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'living',
                'name' => 'リビング関連',
                'name_en' => 'Living Room',
                'description' => 'リビングルームで使用する家具や家電',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'kitchen-bath',
                'name' => 'キッチン・バス関連',
                'name_en' => 'Kitchen & Bathroom',
                'description' => 'キッチンやバスルームで使用する家電や設備',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'bedroom-study',
                'name' => '寝室・書斎関連',
                'name_en' => 'Bedroom & Study',
                'description' => '寝室や書斎で使用する家具',
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'other',
                'name' => 'その他',
                'name_en' => 'Other',
                'description' => 'その他の家財や特殊な荷物',
                'sort_order' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($categories as $categoryData) {
            LuggageCategory::create($categoryData);
        }
    }
}
