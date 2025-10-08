<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\Prefecture;

class PrefectureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 地域データを取得
        $hokkaido = Region::where('code', 1)->first();
        $tohoku = Region::where('code', 2)->first();
        $kanto = Region::where('code', 3)->first();
        $chubu = Region::where('code', 4)->first();
        $kinki = Region::where('code', 5)->first();
        $chugoku = Region::where('code', 6)->first();
        $shikoku = Region::where('code', 7)->first();
        $kyushu = Region::where('code', 8)->first();

        $prefectures = [
            // 北海道
            ['region_id' => $hokkaido->id, 'code' => 1, 'name' => '北海道'],

            // 東北
            ['region_id' => $tohoku->id, 'code' => 2, 'name' => '青森県'],
            ['region_id' => $tohoku->id, 'code' => 3, 'name' => '岩手県'],
            ['region_id' => $tohoku->id, 'code' => 4, 'name' => '宮城県'],
            ['region_id' => $tohoku->id, 'code' => 5, 'name' => '秋田県'],
            ['region_id' => $tohoku->id, 'code' => 6, 'name' => '山形県'],
            ['region_id' => $tohoku->id, 'code' => 7, 'name' => '福島県'],

            // 関東
            ['region_id' => $kanto->id, 'code' => 8, 'name' => '茨城県'],
            ['region_id' => $kanto->id, 'code' => 9, 'name' => '栃木県'],
            ['region_id' => $kanto->id, 'code' => 10, 'name' => '群馬県'],
            ['region_id' => $kanto->id, 'code' => 11, 'name' => '埼玉県'],
            ['region_id' => $kanto->id, 'code' => 12, 'name' => '千葉県'],
            ['region_id' => $kanto->id, 'code' => 13, 'name' => '東京都'],
            ['region_id' => $kanto->id, 'code' => 14, 'name' => '神奈川県'],

            // 中部
            ['region_id' => $chubu->id, 'code' => 15, 'name' => '新潟県'],
            ['region_id' => $chubu->id, 'code' => 16, 'name' => '富山県'],
            ['region_id' => $chubu->id, 'code' => 17, 'name' => '石川県'],
            ['region_id' => $chubu->id, 'code' => 18, 'name' => '福井県'],
            ['region_id' => $chubu->id, 'code' => 19, 'name' => '山梨県'],
            ['region_id' => $chubu->id, 'code' => 20, 'name' => '長野県'],
            ['region_id' => $chubu->id, 'code' => 21, 'name' => '岐阜県'],
            ['region_id' => $chubu->id, 'code' => 22, 'name' => '静岡県'],
            ['region_id' => $chubu->id, 'code' => 23, 'name' => '愛知県'],

            // 近畿
            ['region_id' => $kinki->id, 'code' => 24, 'name' => '三重県'],
            ['region_id' => $kinki->id, 'code' => 25, 'name' => '滋賀県'],
            ['region_id' => $kinki->id, 'code' => 26, 'name' => '京都府'],
            ['region_id' => $kinki->id, 'code' => 27, 'name' => '大阪府'],
            ['region_id' => $kinki->id, 'code' => 28, 'name' => '兵庫県'],
            ['region_id' => $kinki->id, 'code' => 29, 'name' => '奈良県'],
            ['region_id' => $kinki->id, 'code' => 30, 'name' => '和歌山県'],

            // 中国
            ['region_id' => $chugoku->id, 'code' => 31, 'name' => '鳥取県'],
            ['region_id' => $chugoku->id, 'code' => 32, 'name' => '島根県'],
            ['region_id' => $chugoku->id, 'code' => 33, 'name' => '岡山県'],
            ['region_id' => $chugoku->id, 'code' => 34, 'name' => '広島県'],
            ['region_id' => $chugoku->id, 'code' => 35, 'name' => '山口県'],

            // 四国
            ['region_id' => $shikoku->id, 'code' => 36, 'name' => '徳島県'],
            ['region_id' => $shikoku->id, 'code' => 37, 'name' => '香川県'],
            ['region_id' => $shikoku->id, 'code' => 38, 'name' => '愛媛県'],
            ['region_id' => $shikoku->id, 'code' => 39, 'name' => '高知県'],

            // 九州・沖縄
            ['region_id' => $kyushu->id, 'code' => 40, 'name' => '福岡県'],
            ['region_id' => $kyushu->id, 'code' => 41, 'name' => '佐賀県'],
            ['region_id' => $kyushu->id, 'code' => 42, 'name' => '長崎県'],
            ['region_id' => $kyushu->id, 'code' => 43, 'name' => '熊本県'],
            ['region_id' => $kyushu->id, 'code' => 44, 'name' => '大分県'],
            ['region_id' => $kyushu->id, 'code' => 45, 'name' => '宮崎県'],
            ['region_id' => $kyushu->id, 'code' => 46, 'name' => '鹿児島県'],
            ['region_id' => $kyushu->id, 'code' => 47, 'name' => '沖縄県'],
        ];

        foreach ($prefectures as $prefecture) {
            Prefecture::create($prefecture);
        }

        $this->command->info('都道府県データを47件作成しました。');
    }
}
