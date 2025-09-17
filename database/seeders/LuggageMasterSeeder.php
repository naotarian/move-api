<?php

namespace Database\Seeders;

use App\Models\LuggageCategory;
use App\Models\LuggageMaster;
use Illuminate\Database\Seeder;

class LuggageMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // カテゴリーを取得
        $livingCategory = LuggageCategory::where('code', 'living')->first();
        $kitchenBathCategory = LuggageCategory::where('code', 'kitchen-bath')->first();
        $bedroomStudyCategory = LuggageCategory::where('code', 'bedroom-study')->first();
        $otherCategory = LuggageCategory::where('code', 'other')->first();

        $luggageItems = [
            // リビング関連
            [
                'code' => 'tv-large',
                'name' => '大型テレビ',
                'sub_label' => '50インチ以上',
                'category_id' => $livingCategory->id,
                'description' => '50インチ以上の大型テレビ',
                'base_price' => 5000.00,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'tv-medium',
                'name' => '中型テレビ',
                'sub_label' => '32-49インチ',
                'category_id' => $livingCategory->id,
                'description' => '32インチから49インチの中型テレビ',
                'base_price' => 3000.00,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'sofa-3seater',
                'name' => '3人掛けソファ',
                'sub_label' => null,
                'category_id' => $livingCategory->id,
                'description' => '3人掛けのソファ',
                'base_price' => 8000.00,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'sofa-2seater',
                'name' => '2人掛けソファ',
                'sub_label' => null,
                'category_id' => $livingCategory->id,
                'description' => '2人掛けのソファ',
                'base_price' => 6000.00,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'coffee-table',
                'name' => 'コーヒーテーブル',
                'sub_label' => null,
                'category_id' => $livingCategory->id,
                'description' => 'リビング用のコーヒーテーブル',
                'base_price' => 2000.00,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'tv-stand',
                'name' => 'テレビ台',
                'sub_label' => null,
                'category_id' => $livingCategory->id,
                'description' => 'テレビを置くためのテレビ台',
                'base_price' => 1500.00,
                'sort_order' => 6,
                'is_active' => true,
            ],
            [
                'code' => 'bookshelf',
                'name' => '本棚',
                'sub_label' => null,
                'category_id' => $livingCategory->id,
                'description' => '本や雑誌を収納する本棚',
                'base_price' => 2500.00,
                'sort_order' => 7,
                'is_active' => true,
            ],

            // キッチン・バス関連
            [
                'code' => 'refrigerator',
                'name' => '冷蔵庫',
                'sub_label' => null,
                'category_id' => $kitchenBathCategory->id,
                'description' => '家庭用冷蔵庫',
                'base_price' => 8000.00,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'washing-machine',
                'name' => '洗濯機',
                'sub_label' => null,
                'category_id' => $kitchenBathCategory->id,
                'description' => '家庭用洗濯機',
                'base_price' => 6000.00,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'microwave',
                'name' => '電子レンジ',
                'sub_label' => null,
                'category_id' => $kitchenBathCategory->id,
                'description' => '電子レンジ',
                'base_price' => 2000.00,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'rice-cooker',
                'name' => '炊飯器',
                'sub_label' => null,
                'category_id' => $kitchenBathCategory->id,
                'description' => '家庭用炊飯器',
                'base_price' => 1000.00,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'toaster',
                'name' => 'トースター',
                'sub_label' => null,
                'category_id' => $kitchenBathCategory->id,
                'description' => 'パン焼き用トースター',
                'base_price' => 800.00,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'coffee-maker',
                'name' => 'コーヒーメーカー',
                'sub_label' => null,
                'category_id' => $kitchenBathCategory->id,
                'description' => 'コーヒーを淹れるための機器',
                'base_price' => 1500.00,
                'sort_order' => 6,
                'is_active' => true,
            ],

            // 寝室・書斎関連
            [
                'code' => 'bed-double',
                'name' => 'ダブルベッド',
                'sub_label' => null,
                'category_id' => $bedroomStudyCategory->id,
                'description' => 'ダブルサイズのベッド',
                'base_price' => 10000.00,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'bed-single',
                'name' => 'シングルベッド',
                'sub_label' => null,
                'category_id' => $bedroomStudyCategory->id,
                'description' => 'シングルサイズのベッド',
                'base_price' => 7000.00,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'wardrobe',
                'name' => 'クローゼット',
                'sub_label' => null,
                'category_id' => $bedroomStudyCategory->id,
                'description' => '衣類を収納するクローゼット',
                'base_price' => 12000.00,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'desk',
                'name' => 'デスク',
                'sub_label' => null,
                'category_id' => $bedroomStudyCategory->id,
                'description' => '作業用のデスク',
                'base_price' => 5000.00,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'chair',
                'name' => '椅子',
                'sub_label' => null,
                'category_id' => $bedroomStudyCategory->id,
                'description' => 'デスク用の椅子',
                'base_price' => 3000.00,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'code' => 'dresser',
                'name' => 'ドレッサー',
                'sub_label' => null,
                'category_id' => $bedroomStudyCategory->id,
                'description' => '化粧品などを収納するドレッサー',
                'base_price' => 4000.00,
                'sort_order' => 6,
                'is_active' => true,
            ],

            // その他
            [
                'code' => 'bicycle',
                'name' => '自転車',
                'sub_label' => null,
                'category_id' => $otherCategory->id,
                'description' => '自転車',
                'base_price' => 2000.00,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'piano',
                'name' => 'ピアノ',
                'sub_label' => null,
                'category_id' => $otherCategory->id,
                'description' => 'アップライトピアノ',
                'base_price' => 25000.00,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'guitar',
                'name' => 'ギター',
                'sub_label' => null,
                'category_id' => $otherCategory->id,
                'description' => 'アコースティックギター',
                'base_price' => 1500.00,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'code' => 'drum-set',
                'name' => 'ドラムセット',
                'sub_label' => null,
                'category_id' => $otherCategory->id,
                'description' => '電子ドラムセット',
                'base_price' => 15000.00,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'code' => 'plant-large',
                'name' => '大型観葉植物',
                'sub_label' => null,
                'category_id' => $otherCategory->id,
                'description' => '大型の観葉植物',
                'base_price' => 3000.00,
                'sort_order' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($luggageItems as $itemData) {
            LuggageMaster::create($itemData);
        }
    }
}
