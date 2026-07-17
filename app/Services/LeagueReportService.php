<?php

namespace App\Services;

use App\Models\League;

class LeagueReportService
{
    public function __construct(private PromotionRelegationService $promo) {}

    /**
     * Build the full-season report payload: per group, every jornada's
     * per-cancha results, plus the group's final standings.
     */
    public function build(League $league): array
    {
        $league->load(['groups.jornadas', 'players']);
        $playerNames = $league->players()->pluck('full_name', 'id');
        $movement = (int) $league->promotion_relegation;

        $groups = [];

        foreach ($league->groups as $group) {
            $jornadas = [];

            foreach ($group->jornadas->sortBy('number') as $jornada) {
                $breakdown = $this->promo->jornadaBreakdown($jornada, $movement);
                if (empty($breakdown)) continue;

                // Resolve names + attach schedule per cancha
                $canchaModels = $jornada->canchas()->with('pista.sede')->get()->keyBy('id');

                $canchas = collect($breakdown)->map(function ($c) use ($playerNames, $canchaModels) {
                    $c['players'] = collect($c['players'])->map(function ($p) use ($playerNames) {
                        $p['name'] = $playerNames[$p['player_id']] ?? '—';
                        return $p;
                    })->all();

                    $model = $canchaModels->get($c['cancha_id']);
                    $c['date_display'] = $model?->date?->translatedFormat('d/m/Y');
                    $c['time_slot']    = $model?->time_slot;
                    $c['pista']        = $model?->pista?->name;
                    $c['sede']         = $model?->pista?->sede?->name;
                    $c['status']       = $model?->status;
                    return $c;
                })->all();

                $complete = $jornada->canchas()->count() > 0
                    && !$jornada->canchas()
                        ->where('status', '!=', \App\Models\Cancha::STATUS_COMPLETED)
                        ->exists();

                $jornadas[] = [
                    'number'   => $jornada->number,
                    'complete' => $complete,
                    'canchas'  => $canchas,
                ];
            }

            $groups[] = [
                'name'      => $group->name,
                'jornadas'  => $jornadas,
                'standings' => $this->promo->groupSeasonStandings($group, $movement, $playerNames),
            ];
        }

        return [
            'league'       => $league,
            'groups'       => $groups,
            'generated_at' => now()->translatedFormat('d \d\e F \d\e Y, H:i'),
        ];
    }
}
