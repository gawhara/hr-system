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
        //
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
