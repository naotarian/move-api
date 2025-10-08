<?php

namespace App\Services\Store\EstimateBidRight;

use App\Repositories\Store\EstimateBidRight\EstimateBidRightRepositoryInterface as repository;
use App\Services\EstimateService;
use Illuminate\Support\Facades\Auth;

class CreateBidRightService
{
    public function __construct(
        private repository $estimateBidRightRepository,
        private EstimateService $estimateService
    ) {}

    public function execute($estimateId): void
    {
        // ログイン中の店舗を取得
        $store = Auth::guard('store')->user();
        if (!$store) {
            throw new \Exception('店舗認証が無効です', 401);
        }
        $isExists = $this->estimateService->isExists($estimateId);
        if (!$isExists) {
            throw new \Exception('見積もりが見つかりません', 404);
        }
        $estimateBidRight = $this->estimateBidRightRepository->findByEstimateIdAndStoreId($estimateId, $store->id);
        if ($estimateBidRight) {
            throw new \Exception('入札権が既に存在します', 409);
        }
        $this->estimateBidRightRepository->create($estimateId, $store->id);
    }
}
