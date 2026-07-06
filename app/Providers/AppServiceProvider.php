<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Biometric protocol boundary — swap the implementation per brand
        // without touching the pull pipeline (AGENT.md: interface, not
        // hard dependency).
        $this->app->bind(
            \App\Services\Biometric\BiometricConnector::class,
            \App\Services\Biometric\ZktecoConnector::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if (! $user) {
                return;
            }

            $companies = $user->isGroupAdmin()
                ? Company::orderBy('name_ar')->get()
                : $user->companies()->orderBy('name_ar')->get();

            $view->with('companies', $companies);
        });
    }
}
