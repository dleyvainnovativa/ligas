<?php

namespace App\Http\Controllers;

use App\Models\League;
use Illuminate\Http\Request;

/**
 * Manager-facing ads: read-only. Managers can't upload ads (only the admin targets
 * ads to leagues via AdminAdController). The one control a manager has is whether
 * network-wide (global) ads appear on their league.
 */
class AdController extends Controller
{
    public function index(League $league)
    {
        $this->authorize('view', $league);

        $leagueAds = $league->activeAds()->get();
        $globalAds = \App\Models\Ad::where('is_global', true)
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        return view('leagues.ads.index', [
            'league'    => $league,
            'leagueAds' => $leagueAds,
            'globalAds' => $globalAds,
        ]);
    }

    public function updateSettings(Request $request, League $league)
    {
        $this->authorize('update', $league);

        $data = $request->validate([
            'ads_show_global' => ['required', 'boolean'],
        ]);

        $league->update(['ads_show_global' => $data['ads_show_global']]);

        app(\App\Services\PublicCacheService::class)->bust($league);

        return response()->json([
            'ok'              => true,
            'ads_show_global' => $league->ads_show_global,
            'message'         => $league->ads_show_global
                ? 'Se mostrarán los anuncios globales en tu liga.'
                : 'Se ocultaron los anuncios globales en tu liga.',
        ]);
    }
}
