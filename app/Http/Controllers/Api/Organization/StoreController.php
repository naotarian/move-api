<?php

namespace App\Http\Controllers\Api\Organization;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\UseCases\Organization\Store\GetStoreListUseCase;
use App\UseCases\Organization\Store\GetStoreDetailUseCase;
use App\UseCases\Organization\Store\UpdatePaymentMethodUseCase;

class StoreController extends Controller
{
    public function __construct(
        private GetStoreListUseCase $getStoreListUseCase,
        private GetStoreDetailUseCase $getStoreDetailUseCase,
        private UpdatePaymentMethodUseCase $updatePaymentMethodUseCase
    ) {}

    /**
     * 認証済み組織の店舗一覧を取得
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            Log::info('StoreController: Retrieving store list for organization', [
                'organization_id' => auth('organization')->id(),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'filters' => $request->query()
            ]);

            // クエリパラメータからフィルタを取得
            $filters = [];
            if ($request->has('name')) {
                $filters['name'] = $request->query('name');
            }
            if ($request->has('email')) {
                $filters['email'] = $request->query('email');
            }
            if ($request->has('phone')) {
                $filters['phone'] = $request->query('phone');
            }
            if ($request->has('address')) {
                $filters['address'] = $request->query('address');
            }
            if ($request->has('status')) {
                $filters['status'] = $request->query('status');
            }
            if ($request->has('is_verified')) {
                $filters['is_verified'] = $request->query('is_verified') === '1';
            }

            $result = $this->getStoreListUseCase->execute($filters);

            return response()->json([
                'success' => true,
                'message' => '店舗一覧を取得しました',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            Log::error('StoreController: Error retrieving store list', [
                'error' => $e->getMessage(),
                'organization_id' => auth('organization')->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => '店舗一覧の取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $store = $this->getStoreDetailUseCase->execute($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }

        return response()->json([
            'success' => true,
            'data' => $store
        ]);
    }

    public function updatePaymentMethod(string $id, Request $request): JsonResponse
    {
        try {
            $this->updatePaymentMethodUseCase->execute($id, $request->payment_method_id);
            return response()->json([
                'success' => true,
                'message' => '支払い方法を更新しました'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $e->getCode());
        }
    }
}
