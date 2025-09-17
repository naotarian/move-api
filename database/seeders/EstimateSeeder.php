<?php

namespace Database\Seeders;

use App\Models\Estimate;
use App\Models\EstimateLuggage;
use App\Models\EstimateResult;
use App\Models\LuggageMaster;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class EstimateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 100件のテスト用見積もりデータを作成
        for ($i = 1; $i <= 100; $i++) {
            $estimateData = $this->generateEstimateData($i);
            // 基本情報と住所情報を分離
            $basicData = [
                'name' => $estimateData['name'],
                'name_furigana' => $estimateData['name_furigana'],
                'phone' => $estimateData['phone'],
                'email' => $estimateData['email'],
                'people_count' => (int) str_replace(['人', '以上'], '', $estimateData['people_count']),
                'moving_date_type' => $estimateData['moving_date_type'] === '決まっている' ? 'decided' : 'undecided',
                'moving_specific_date' => $estimateData['moving_specific_date'] ?? null,
                'work_start_time_type' => $estimateData['work_start_time_type'] === '指定する' ? 'specific' : 'anytime',
                'work_start_time' => $this->convertWorkStartTime($estimateData['work_start_time'] ?? null),
                'other_luggage' => $estimateData['other_luggage'],
                'status' => $this->convertStatus($estimateData['status']),
            ];

            $fromAddressData = [
                'zipcode' => $estimateData['from_zipcode'],
                'prefecture' => $estimateData['from_prefecture'],
                'street_address' => $estimateData['from_street_address'],
                'building_details' => $estimateData['from_building_details'],
                'building_type' => $this->convertBuildingType($estimateData['from_building_type']),
                'room_layout' => $estimateData['from_room_layout'],
                'floor' => $estimateData['from_floor'],
                'elevator' => $estimateData['from_elevator'] === 'あり' ? 'yes' : 'no',
            ];

            $toAddressData = [
                'zipcode' => $estimateData['to_zipcode'],
                'prefecture' => $estimateData['to_prefecture'],
                'street_address' => $estimateData['to_street_address'],
                'building_details' => $estimateData['to_building_details'],
                'building_type' => $this->convertBuildingType($estimateData['to_building_type']),
                'room_layout' => $estimateData['to_room_layout'],
                'floor' => $estimateData['to_floor'],
                'elevator' => $estimateData['to_elevator'] === 'あり' ? 'yes' : 'no',
            ];

            // 見積もりを作成
            $estimate = Estimate::create($basicData);

            // 引越し元住所を作成
            $fromAddressData['estimate_id'] = $estimate->id;
            $estimate->movingFromAddress()->create($fromAddressData);

            // 引越し先住所を作成
            $toAddressData['estimate_id'] = $estimate->id;
            $estimate->movingToAddress()->create($toAddressData);

            // 荷物情報を追加
            $this->createLuggageItems($estimate);

            // 見積もり結果を追加（一部の見積もりのみ）
            if (in_array($estimate->status, ['published', 'closed'])) {
                $this->createEstimateResults($estimate);
            }
        }
    }

    /**
     * 見積もりデータを生成
     */
    private function generateEstimateData(int $index): array
    {
        $names = ['田中', '佐藤', '山田', '鈴木', '高橋', '渡辺', '伊藤', '中村', '小林', '加藤', '吉田', '山本', '松本', '井上', '木村', '林', '清水', '森', '池田', '橋本'];
        $firstNames = ['太郎', '花子', '次郎', '美咲', '健一', '由美', '正雄', '恵子', '和也', '直子', '修一', '真理', '博之', '智子', '誠', '香織', '慎一', '麻衣', '大輔', '優子'];
        $prefectures = ['東京都', '神奈川県', '千葉県', '埼玉県', '大阪府', '愛知県', '福岡県', '兵庫県', '北海道', '静岡県', '茨城県', '広島県', '京都府', '宮城県', '新潟県', '長野県', '岐阜県', '群馬県', '栃木県', '岡山県'];
        $buildingTypes = ['マンション', 'アパート', '戸建て', 'その他'];
        $roomLayouts = ['ワンルーム', '1K', '1DK', '1LDK', '2K', '2DK', '2LDK', '3K', '3DK', '3LDK', '4K', '4DK', '4LDK', '5K以上'];
        $workStartTimes = ['午前中', '12時~15時', '15時以降'];
        $movingPeriods = ['上旬', '中旬', '下旬'];
        $statuses = ['published', 'draft', 'closed'];
        $peopleCounts = [1, 2, 3, 4]; // 4は4人以上を表す

        $name = $names[0] . $firstNames[0]; // 田中太郎
        $furigana = $this->generateFurigana($name);
        $movingDateType = 'decided'; // 決まっている
        $workStartTimeType = 'specific'; // 指定する
        $status = 'published'; // 公開中に固定
        $peopleCount = 2; // 2人

        $fromPrefecture = $prefectures[0]; // 東京都
        $toPrefecture = $prefectures[1]; // 神奈川県

        $data = [
            'name' => $name,
            'name_furigana' => $furigana,
            'phone' => '090-1234-5678',
            'email' => strtolower($furigana) . $index . '@example.com',
            'people_count' => $peopleCount,
            'moving_date_type' => $movingDateType,
            'work_start_time_type' => $workStartTimeType,
            'from_zipcode' => '100-0001',
            'from_prefecture' => $fromPrefecture,
            'from_street_address' => '千代田区千代田1-1-1',
            'from_building_details' => 'マンション101',
            'from_building_type' => $buildingTypes[0], // マンション
            'from_room_layout' => $roomLayouts[5], // 2LDK
            'from_floor' => '3階',
            'from_elevator' => 'yes',
            'to_zipcode' => '150-0001',
            'to_prefecture' => $toPrefecture,
            'to_street_address' => '渋谷区神宮前1-1-1',
            'to_building_details' => 'アパート201',
            'to_building_type' => $buildingTypes[1], // アパート
            'to_room_layout' => $roomLayouts[5], // 2LDK
            'to_floor' => '2階',
            'to_elevator' => 'no',
            'other_luggage' => '大型の観葉植物、アンティーク家具',
            'status' => $status,
        ];

        // 引越し日が決まっている場合
        if ($movingDateType === 'decided') {
            $data['moving_specific_date'] = Carbon::now()->addDays(30);
        } else {
            $data['moving_year_month'] = Carbon::now()->addMonths(1)->format('Y年n月');
            $data['moving_period'] = $movingPeriods[1]; // 中旬
        }

        // 作業開始時間が指定する場合
        if ($workStartTimeType === 'specific') {
            $data['work_start_time'] = $workStartTimes[0]; // 午前中
        }

        return $data;
    }

    /**
     * フリガナを生成
     */
    private function generateFurigana(string $name): string
    {
        $furiganaMap = [
            '田中' => 'タナカ', '佐藤' => 'サトウ', '山田' => 'ヤマダ', '鈴木' => 'スズキ', '高橋' => 'タカハシ',
            '渡辺' => 'ワタナベ', '伊藤' => 'イトウ', '中村' => 'ナカムラ', '小林' => 'コバヤシ', '加藤' => 'カトウ',
            '吉田' => 'ヨシダ', '山本' => 'ヤマモト', '松本' => 'マツモト', '井上' => 'イノウエ', '木村' => 'キムラ',
            '林' => 'ハヤシ', '清水' => 'シミズ', '森' => 'モリ', '池田' => 'イケダ', '橋本' => 'ハシモト',
            '太郎' => 'タロウ', '花子' => 'ハナコ', '次郎' => 'ジロウ', '美咲' => 'ミサキ', '健一' => 'ケンイチ',
            '由美' => 'ユミ', '正雄' => 'マサオ', '恵子' => 'ケイコ', '和也' => 'カズヤ', '直子' => 'ナオコ',
            '修一' => 'シュウイチ', '真理' => 'マリ', '博之' => 'ヒロユキ', '智子' => 'トモコ', '誠' => 'マコト',
            '香織' => 'カオリ', '慎一' => 'シンイチ', '麻衣' => 'マイ', '大輔' => 'ダイスケ', '優子' => 'ユウコ'
        ];

        $furigana = '';
        for ($i = 0; $i < mb_strlen($name, 'UTF-8'); $i++) {
            $char = mb_substr($name, $i, 1, 'UTF-8');
            $furigana .= $furiganaMap[$char] ?? $char;
        }

        return $furigana;
    }

    /**
     * 住所を生成
     */
    private function generateStreetAddress(): string
    {
        $cities = ['渋谷区', '新宿区', '港区', '千代田区', '中央区', '文京区', '台東区', '墨田区', '江東区', '品川区'];
        $streets = ['1-1-1', '2-2-2', '3-3-3', '4-4-4', '5-5-5', '6-6-6', '7-7-7', '8-8-8', '9-9-9', '10-10-10'];
        
        return $cities[rand(0, count($cities) - 1)] . $streets[rand(0, count($streets) - 1)];
    }

    /**
     * 建物詳細を生成
     */
    private function generateBuildingDetails(): string
    {
        $buildingNames = ['マンション', 'アパート', 'タワーマンション', '高層マンション', 'レジデンス'];
        $roomNumbers = ['101', '201', '301', '401', '501', '601', '701', '801', '901', '1001'];
        
        return $buildingNames[rand(0, count($buildingNames) - 1)] . $roomNumbers[rand(0, count($roomNumbers) - 1)];
    }

    /**
     * 階数を生成
     */
    private function generateFloor(): string
    {
        $floors = ['1階', '2階', '3階', '4階', '5階', '6階', '7階', '8階', '9階', '10階', '15階', '20階', '25階', '30階'];
        return $floors[rand(0, count($floors) - 1)];
    }

    /**
     * その他の荷物を生成
     */
    private function generateOtherLuggage(): string
    {
        $luggageItems = [
            '大型の観葉植物',
            'アンティーク家具',
            '楽器（ピアノ）',
            '楽器（ギター）',
            '楽器（ドラムセット）',
            '大型家具',
            '美術品',
            '骨董品',
            '大型家電',
            '特殊な荷物'
        ];
        
        $count = rand(1, 3);
        $selected = array_rand($luggageItems, $count);
        if (!is_array($selected)) {
            $selected = [$selected];
        }
        
        return implode('、', array_map(fn($index) => $luggageItems[$index], $selected));
    }

    /**
     * 荷物情報を作成
     */
    private function createLuggageItems(Estimate $estimate): void
    {
        // 全ての荷物マスタを取得
        $allLuggage = LuggageMaster::where('is_active', true)->get();
        
        if ($allLuggage->isEmpty()) {
            return;
        }

        // ランダムに5-15個の荷物を選択
        $luggageCount = rand(5, 15);
        $selectedLuggage = $allLuggage->random(min($luggageCount, $allLuggage->count()));

        foreach ($selectedLuggage as $luggage) {
            // 数量もランダムに設定（1-5個）
            $quantity = rand(1, 5);
            
            EstimateLuggage::create([
                'estimate_id' => $estimate->id,
                'luggage_id' => $luggage->id,
                'quantity' => $quantity,
            ]);
        }
    }

    /**
     * 見積もり結果を作成
     */
    private function createEstimateResults(Estimate $estimate): void
    {
        $companies = [
            '引越しのサカイ', 'アート引越センター', '引越しのプロ', 'ハート引越センター', '引越しの達人',
            'スーパー引越センター', '引越しの匠', 'プロ引越センター', '引越しのエキスパート', '引越しのマスター'
        ];
        
        $contacts = ['田中', '佐藤', '山田', '鈴木', '高橋', '渡辺', '伊藤', '中村', '小林', '加藤'];
        $notes = [
            '丁寧な梱包サービス付き', '24時間サポート対応', '最安値保証', '迅速対応', '安心の実績',
            'プロのスタッフが対応', 'お客様満足度No.1', '全国対応可能', '追加料金なし', '品質保証付き'
        ];
        
        // 2-5社の見積もり結果を作成
        $companyCount = rand(2, 5);
        $selectedCompanies = array_rand($companies, $companyCount);
        
        if (!is_array($selectedCompanies)) {
            $selectedCompanies = [$selectedCompanies];
        }
        
        foreach ($selectedCompanies as $index) {
            $basePrice = rand(50000, 150000);
            $estimatedPrice = $basePrice + rand(-10000, 10000);
            
            EstimateResult::create([
                'estimate_id' => $estimate->id,
                'company_name' => $companies[$index],
                'company_contact' => $contacts[rand(0, count($contacts) - 1)] . '営業',
                'company_phone' => '0' . rand(3, 9) . '-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'company_email' => strtolower($contacts[rand(0, count($contacts) - 1)]) . '@company' . ($index + 1) . '.co.jp',
                'estimated_price' => $estimatedPrice,
                'notes' => $notes[rand(0, count($notes) - 1)],
                'status' => $estimate->status === 'closed' && rand(0, 1) ? 'accepted' : 'pending',
                'quoted_at' => Carbon::now()->subHours(rand(1, 24)),
                'expires_at' => Carbon::now()->addDays(rand(3, 14)),
            ]);
        }
    }

    /**
     * 作業開始時間を変換
     */
    private function convertWorkStartTime(?string $time): ?string
    {
        if (!$time) return null;
        
        return match($time) {
            '午前中' => 'morning',
            '12時~15時' => 'afternoon',
            '15時以降' => 'evening',
            default => null
        };
    }

    /**
     * ステータスを変換
     */
    private function convertStatus(string $status): string
    {
        return match($status) {
            'pending' => 'draft',
            'processing' => 'published',
            'completed' => 'closed',
            'published' => 'published',
            'draft' => 'draft',
            'closed' => 'closed',
            default => 'draft'
        };
    }

    /**
     * 建物タイプを変換
     */
    private function convertBuildingType(string $type): string
    {
        return match($type) {
            'マンション' => 'mansion',
            'アパート' => 'apartment',
            '戸建て' => 'house',
            default => 'other'
        };
    }
}
