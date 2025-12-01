<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ChallengeProgress;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
        $this->app->singleton('challenge.progress', function () {
            return new ChallengeProgress();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
