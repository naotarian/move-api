<?php

namespace App\Providers;

use App\Repositories\EstimateRepository;
use App\Repositories\Portal\EstimateRepository as PortalEstimateRepository;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
