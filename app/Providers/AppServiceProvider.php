<?php

namespace App\Providers;

use App\Services\BetaModeService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(BetaModeService::class);
    }

    public function boot(): void
    {
        if (request()->header('x-forwarded-proto') === 'https' || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer('*', function ($view) {
            $view->with('beta', app(BetaModeService::class));
        });
    }
}
