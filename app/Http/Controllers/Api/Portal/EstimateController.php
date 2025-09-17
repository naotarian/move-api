<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\UseCases\Portal\Estimate\CreateEstimateUseCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class EstimateController extends Controller
{
    public function __construct(
        private CreateEstimateUseCase $createEstimateUseCase
    ) {}

    /**
     * 見積もり作成（フロントエンド用）
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // バリデーション
            \Log::info($request->all());
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:100',
                'name_furigana' => 'required|string|max:100',
                'phone' => 'required|string|max:20',
                'email' => 'required|email|max:255',
                'moving_date_type' => 'required|in:undecided,decided',
                'moving_specific_date' => 'nullable|date',
                'moving_year_month' => 'nullable|string',
                'moving_period' => 'nullable|in:early,middle,late',
                'people_count' => 'required|integer|min:1|max:4',
                'work_start_time_type' => 'required|in:anytime,specific',
                'work_start_time' => 'nullable|in:morning,afternoon,evening',
                'from_zipcode' => 'required|string|max:10',
                'from_prefecture' => 'required|string|max:100',
                'from_street_address' => 'required|string|max:255',
                'from_building_details' => 'nullable|string|max:255',
                'from_building_type' => 'required|string|max:50',
                'from_room_layout' => 'required|string|max:50',
                'from_floor' => 'required|string|max:20',
                'from_elevator' => 'required|in:yes,no',
                'to_zipcode' => 'required|string|max:10',
                'to_prefecture' => 'required|string|max:100',
                'to_street_address' => 'required|string|max:255',
                'to_building_details' => 'nullable|string|max:255',
                'to_building_type' => 'required|string|max:50',
                'to_room_layout' => 'required|string|max:50',
                'to_floor' => 'required|string|max:20',
                'to_elevator' => 'required|in:yes,no',
                'luggage_items' => 'array',
                'luggage_items.*.id' => 'required|string',
                'luggage_items.*.quantity' => 'required|integer|min:1',
                'other_luggage' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'バリデーションエラーが発生しました',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // UseCaseを実行
            $result = $this->createEstimateUseCase->execute($request->all());

            return response()->json($result, 201);
        } catch (\Exception $e) {
            \Log::error('見積もり作成エラー: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '見積もりの作成に失敗しました',
            ], 500);
        }
    }
}
