<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\UseCases\Store\Customer\GetCustomersUseCase;

class CustomerController extends Controller
{
    public function __construct(
        private GetCustomersUseCase $getCustomersUseCase
    ) {}

    /**
     * 顧客一覧取得
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // 検索条件を配列にまとめる
        $searchConditions = [
            'keyword' => $request->get('search'),
            'ranking' => $request->get('ranking'),
        ];

        $customers = $this->getCustomersUseCase->execute($perPage, $page, $searchConditions);
        return response()->json($customers);
    }
}
