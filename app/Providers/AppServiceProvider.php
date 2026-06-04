<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\GameMatch;
use Illuminate\Support\Facades\Route;
use App\Services\MatchProposalService;
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
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        \Carbon\Carbon::setLocale('es');
        Route::model('match', GameMatch::class);

        View::composer('leagues.partials._panel-nav', function ($view) {
            $league = $view->getData()['league'] ?? null;
            if ($league) {
                $pending = app(MatchProposalService::class)->pendingCountForLeague($league->id);
                $view->with('pendingProposalsCount', $pending);
            }
        });
    }
}
