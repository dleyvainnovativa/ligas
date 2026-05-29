<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\GameMatch;
use Illuminate\Support\Facades\Route;

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
        \Carbon\Carbon::setLocale('es');
        Route::model('match', GameMatch::class);
    }
}
