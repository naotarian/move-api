<?php

namespace App\Providers;

use App\Repositories\EstimateRepository;
use App\Repositories\Portal\EstimateRepository as PortalEstimateRepository;
use App\Repositories\Store\EstimateBidRight\EstimateBidRightRepository;
use App\Repositories\Store\EstimateBidRight\EstimateBidRightRepositoryInterface;
use App\Repositories\Store\Payment\PaymentRepository;
use App\Repositories\Store\Payment\PaymentRepositoryInterface;
use App\Repositories\Store\Bid\BidRepository;
use App\Repositories\Store\Bid\BidRepositoryInterface;
// Service・UseCase層は自動解決されるためインポート不要
use App\Repositories\Store\PurchaseHistory\PurchaseHistoryRepositoryInterface;
use App\Repositories\Store\PurchaseHistory\PurchaseHistoryRepository;
use App\Services\Portal\Estimate\EmailVerificationService;
use App\Services\Portal\Estimate\SmsVerificationService;
use App\Services\AuthMailService;
use App\Services\SmsService;
// UseCase層も自動解決されるためインポート不要
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(EstimateRepository::class, function ($app) {
            return new EstimateRepository();
        });

        // Portal namespace repository bindings
        $this->app->bind(PortalEstimateRepository::class, function ($app) {
            return new PortalEstimateRepository();
        });

        // Repository層のインターフェースを実装クラスにバインド
        $this->app->bind(EstimateBidRightRepositoryInterface::class, EstimateBidRightRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, PaymentRepository::class);
        $this->app->bind(BidRepositoryInterface::class, BidRepository::class);
        $this->app->bind(PurchaseHistoryRepositoryInterface::class, PurchaseHistoryRepository::class);

        // Repository層の具象クラスも自動解決に任せる（コンストラクタが空の場合）

        // Service層も具象クラスのみなので、Laravelの自動解決に任せる

        // UseCase・Service層は具象クラスのみなので、Laravelの自動解決に任せる
        // Repository層のインターフェースが適切にバインドされていれば自動で解決される

        // Portal系のService層 - 認証サービス
        $this->app->bind(EmailVerificationService::class, function ($app) {
            return new EmailVerificationService(
                $app->make(AuthMailService::class)
            );
        });

        $this->app->bind(SmsVerificationService::class, function ($app) {
            return new SmsVerificationService(
                $app->make(SmsService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
