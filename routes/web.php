<?php

use App\Http\Controllers\AdController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CanchaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GameMatchController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\JornadaController;
use App\Http\Controllers\LeagueController;
use App\Http\Controllers\PairController;
use App\Http\Controllers\PistaController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\SedeController;
use App\Http\Controllers\StandingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'));

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/auth/session', [AuthController::class, 'sessionLogin'])->name('auth.session');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ... all your existing routes ...


// Manager area
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('leagues', LeagueController::class);
    Route::prefix('leagues/{league}')->name('leagues.')->group(function () {
        // Sedes
        Route::post('sedes',               [SedeController::class, 'store'])->name('sedes.store');
        Route::put('sedes/{sede}',         [SedeController::class, 'update'])->name('sedes.update');
        Route::delete('sedes/{sede}',      [SedeController::class, 'destroy'])->name('sedes.destroy');

        // Pistas (nested under sede)
        Route::post('sedes/{sede}/pistas',                [PistaController::class, 'store'])->name('pistas.store');
        Route::put('sedes/{sede}/pistas/{pista}',         [PistaController::class, 'update'])->name('pistas.update');
        Route::delete('sedes/{sede}/pistas/{pista}',      [PistaController::class, 'destroy'])->name('pistas.destroy');

        // Players
        Route::get('players',                   [PlayerController::class, 'index'])->name('players.index');
        Route::post('players',                  [PlayerController::class, 'store'])->name('players.store');
        Route::put('players/{player}',          [PlayerController::class, 'update'])->name('players.update');
        Route::delete('players/{player}',       [PlayerController::class, 'destroy'])->name('players.destroy');
        Route::post('players/import/preview',   [PlayerController::class, 'importPreview'])->name('players.import.preview');
        Route::post('players/import',           [PlayerController::class, 'import'])->name('players.import');

        // Groups
        Route::get('groups',                       [GroupController::class, 'index'])->name('groups.index');
        Route::post('groups',                      [GroupController::class, 'store'])->name('groups.store');
        Route::put('groups/{group}',               [GroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}',            [GroupController::class, 'destroy'])->name('groups.destroy');
        Route::post('groups/move-player',          [GroupController::class, 'movePlayer'])->name('groups.move-player');
        Route::post('groups/move-pair',            [GroupController::class, 'movePair'])->name('groups.move-pair');

        // Pairs
        Route::post('pairs',          [PairController::class, 'store'])->name('pairs.store');
        Route::put('pairs/{pair}',    [PairController::class, 'update'])->name('pairs.update');
        Route::delete('pairs/{pair}', [PairController::class, 'destroy'])->name('pairs.destroy');

        // Jornadas
        Route::get('groups/{group}/jornadas',                       [JornadaController::class, 'index'])->name('jornadas.index');
        Route::post('groups/{group}/jornadas',                      [JornadaController::class, 'store'])->name('jornadas.store');
        Route::get('groups/{group}/jornadas/{jornada}',             [JornadaController::class, 'show'])->name('jornadas.show');
        Route::put('groups/{group}/jornadas/{jornada}',             [JornadaController::class, 'update'])->name('jornadas.update');
        Route::delete('groups/{group}/jornadas/{jornada}',          [JornadaController::class, 'destroy'])->name('jornadas.destroy');
        Route::post('groups/{group}/jornadas/{jornada}/auto-fill',  [JornadaController::class, 'autoFill'])->name('jornadas.auto-fill');

        // Canchas (within a jornada)
        Route::post('groups/{group}/jornadas/{jornada}/canchas',                  [CanchaController::class, 'store'])->name('canchas.store');
        Route::put('groups/{group}/jornadas/{jornada}/canchas/{cancha}',          [CanchaController::class, 'update'])->name('canchas.update');
        Route::delete('groups/{group}/jornadas/{jornada}/canchas/{cancha}',       [CanchaController::class, 'destroy'])->name('canchas.destroy');
        Route::post('groups/{group}/jornadas/{jornada}/canchas/assign',           [CanchaController::class, 'assign'])->name('canchas.assign');

        // Scheduling grid
        Route::get('groups/{group}/jornadas/{jornada}/grid', [GameMatchController::class, 'gridIndex'])
            ->name('matches.grid');
        Route::put('groups/{group}/jornadas/{jornada}/matches/{match}/schedule', [GameMatchController::class, 'schedule'])
            ->name('matches.schedule');
        Route::post('groups/{group}/jornadas/{jornada}/canchas/{cancha}/auto-fit', [GameMatchController::class, 'autoFit'])
            ->name('matches.auto-fit');
        Route::get('groups/{group}/jornadas/{jornada}/conflicts', [GameMatchController::class, 'conflicts'])
            ->name('matches.conflicts');

        // Match results
        Route::get(
            'groups/{group}/jornadas/{jornada}/matches/{match}/result',
            [GameMatchController::class, 'showResult']
        )->name('matches.show-result');
        Route::put(
            'groups/{group}/jornadas/{jornada}/matches/{match}/result',
            [GameMatchController::class, 'saveResult']
        )->name('matches.save-result');
        Route::delete(
            'groups/{group}/jornadas/{jornada}/matches/{match}/proposal/{proposal}',
            [GameMatchController::class, 'rejectProposal']
        )->name('matches.reject-proposal');

        // Standings
        Route::get('standings', [StandingsController::class, 'index'])->name('standings.index');
        Route::get('groups/{group}/standings', [StandingsController::class, 'group'])->name('standings.group');

        // Ads
        Route::get('ads',              [AdController::class, 'index'])->name('ads.index');
        Route::post('ads',             [AdController::class, 'store'])->name('ads.store');
        Route::post('ads/{ad}',        [AdController::class, 'update'])->name('ads.update'); // POST because of file upload
        Route::delete('ads/{ad}',      [AdController::class, 'destroy'])->name('ads.destroy');
        Route::post('ads/reorder',     [AdController::class, 'reorder'])->name('ads.reorder');
    });
});

// PUBLIC: propose a score
Route::post('/{slug}/matches/{match}/propose', [\App\Http\Controllers\PublicMatchProposalController::class, 'store'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.match.propose');

// Public league page — MUST be last among the slug routes
Route::get('/{slug}', [\App\Http\Controllers\PublicLeagueController::class, 'show'])
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('public.league');
