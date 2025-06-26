<?php

namespace App\Providers;

use App\Contracts\Repositories\PackageRepositoryContract;
use App\Contracts\Repositories\ProjectRepositoryContract;
use App\Contracts\Services\PackagistServiceContract;
use App\Repositories\PackageRepository;
use App\Repositories\ProjectRepository;
use App\Services\PackagistService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProjectRepositoryContract::class, ProjectRepository::class);
        $this->app->bind(PackageRepositoryContract::class, PackageRepository::class);
        $this->app->bind(PackagistServiceContract::class, PackagistService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
