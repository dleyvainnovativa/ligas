<?php

namespace App\Services;

use App\Models\League;
use Illuminate\Support\Facades\Cache;

class PublicCacheService
{
    public function bust(League $league): void
    {
        Cache::forget("public_league:{$league->id}:inicio:v1");
        Cache::forget("public_league:{$league->id}:calendario:v1");
        Cache::forget("public_league:{$league->id}:clasificacion:v1");
        Cache::forget("public_league:{$league->id}:jugadores:v1");

        // Bust all jornada-specific caches (we don't know the numbers, so iterate)
        foreach ($league->groups as $group) {
            foreach ($group->jornadas as $j) {
                Cache::forget("public_league:{$league->id}:jornada:{$j->number}:v1");
            }
        }

        // Reglas is rarely-changing; bust only when league config changes
        // Keep this method idempotent — busting an unset key is a no-op.
        Cache::forget("public_league:{$league->id}:reglas:v1");
    }
}
