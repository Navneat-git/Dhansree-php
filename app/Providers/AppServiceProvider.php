<?php

namespace App\Providers;

use App\Support\Auth\AdminGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::extend('admin-session', function ($app, $name, array $config) {
            return new AdminGuard($app['request']);
        });
    }
}
