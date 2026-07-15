<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        // Setiap ability yang dicek lewat @can()/Gate::allows() dipetakan
        // langsung ke permission role user (bukan policy per-model), supaya
        // permission baru otomatis kepakai tanpa perlu daftar Gate manual.
        Gate::before(function (User $user, string $ability) {
            return $user->hasPermission($ability) ?: null;
        });
    }
}
