<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Organization\Store\StoreRepositoryInterface;
use App\Repositories\Organization\Store\StoreRepository;
use App\Repositories\Organization\PaymentInformation\PaymentInformationRepositoryInterface;
use App\Repositories\Organization\PaymentInformation\PaymentInformationRepository;
use App\Repositories\Organization\PaymentSetting\PaymentSettingRepositoryInterface;
use App\Repositories\Organization\PaymentSetting\PaymentSettingRepository;

class OrganizationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository層のみインターフェースを使用
        $this->app->bind(
            StoreRepositoryInterface::class,
            StoreRepository::class
        );
        $this->app->bind(
            PaymentInformationRepositoryInterface::class,
            PaymentInformationRepository::class
        );
        $this->app->bind(
            PaymentSettingRepositoryInterface::class,
            PaymentSettingRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
