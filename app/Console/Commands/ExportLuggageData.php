<?php

namespace App\Console\Commands;

use App\Models\LuggageCategory;
use App\Models\LuggageMaster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ExportLuggageData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'luggage:export {--path=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export luggage master data to JSON files for frontend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Exporting luggage master data...');

        // デフォルトの出力パス
        $defaultPath = '/var/www/html/json';
        $outputPath = $this->option('path') ?: $defaultPath;

        // 出力ディレクトリが存在しない場合は作成
        if (!File::exists($outputPath)) {
            File::makeDirectory($outputPath, 0755, true);
            $this->info("Created directory: {$outputPath}");
        }

        // カテゴリーデータをエクスポート
        $this->exportCategories($outputPath);

        // 荷物マスタデータをエクスポート
        $this->exportLuggageMaster($outputPath);

        // 統合データをエクスポート
        $this->exportCombinedData($outputPath);

        $this->info('Export completed successfully!');
    }

    /**
     * カテゴリーデータをエクスポート
     */
    private function exportCategories(string $outputPath): void
    {
        $categories = LuggageCategory::active()
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'nameEn' => $category->name_en,
                    'description' => $category->description,
                    'sortOrder' => $category->sort_order,
                ];
            });

        $filePath = $outputPath . '/luggage-categories.json';
        File::put($filePath, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported categories to: {$filePath}");
    }

    /**
     * 荷物マスタデータをエクスポート
     */
    private function exportLuggageMaster(string $outputPath): void
    {
        $luggageItems = LuggageMaster::with('category')
            ->active()
            ->ordered()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'code' => $item->code,
                    'name' => $item->name,
                    'subLabel' => $item->sub_label,
                    'categoryId' => $item->category_id,
                    'categoryCode' => $item->category->code,
                    'categoryName' => $item->category->name,
                    'description' => $item->description,
                    'basePrice' => $item->base_price,
                    'sortOrder' => $item->sort_order,
                ];
            });

        $filePath = $outputPath . '/luggage-master.json';
        File::put($filePath, json_encode($luggageItems, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported luggage master to: {$filePath}");
    }

    /**
     * 統合データをエクスポート（カテゴリー別にグループ化）
     */
    private function exportCombinedData(string $outputPath): void
    {
        $categories = LuggageCategory::with(['luggageItems' => function ($query) {
            $query->active()->ordered();
        }])
            ->active()
            ->ordered()
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'nameEn' => $category->name_en,
                    'description' => $category->description,
                    'sortOrder' => $category->sort_order,
                    'items' => $category->luggageItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'code' => $item->code,
                            'name' => $item->name,
                            'subLabel' => $item->sub_label,
                            'description' => $item->description,
                            'basePrice' => $item->base_price,
                            'sortOrder' => $item->sort_order,
                        ];
                    }),
                ];
            });

        $filePath = $outputPath . '/luggage-data.json';
        File::put($filePath, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info("Exported combined data to: {$filePath}");
    }
}
