<?php

namespace App\Services;

use App\Models\League;
use App\Models\Manager;

class TierService
{
    /**
     * Get the manager's effective tier.
     * Honors expiration (`tier_until`): if expired, drops to free.
     */
    public function tierFor(Manager $manager): string
    {
        $tier = $manager->tier ?? 'free';

        if ($manager->tier_until && $manager->tier_until->isPast()) {
            return 'free';
        }

        return in_array($tier, ['free', 'plus', 'pro'], true) ? $tier : 'free';
    }

    /** Full config for the manager's tier. */
    public function configFor(Manager $manager): array
    {
        return config('tiers.' . $this->tierFor($manager));
    }

    /** Get a specific limit for the manager. Returns null if unlimited. */
    public function limit(Manager $manager, string $limitKey): ?int
    {
        return $this->configFor($manager)['limits'][$limitKey] ?? null;
    }

    /** Returns true if the manager would exceed the limit by adding `delta` more. */
    public function wouldExceed(Manager $manager, string $limitKey, int $currentCount, int $delta = 1): bool
    {
        $limit = $this->limit($manager, $limitKey);
        if ($limit === null) return false;
        return ($currentCount + $delta) > $limit;
    }

    // ============================================================
    // Specific check methods — one per limit, called from controllers
    // ============================================================

    public function canCreateLeague(Manager $manager): bool
    {
        $current = $this->activeLeagueCount($manager);
        return !$this->wouldExceed($manager, 'active_leagues', $current);
    }

    public function canAddPlayer(League $league, int $delta = 1): bool
    {
        $manager = $league->manager;
        $current = $league->players()->count();
        return !$this->wouldExceed($manager, 'players_per_league', $current, $delta);
    }

    public function canAddJornada(League $league): bool
    {
        $manager = $league->manager;
        $current = $league->groups()->withCount('jornadas')->get()->sum('jornadas_count');
        return !$this->wouldExceed($manager, 'jornadas_per_league', $current);
    }

    // ============================================================
    // Snapshot used in UI (shows X / limit in chips)
    // ============================================================

    public function snapshot(Manager $manager): array
    {
        $tierKey = $this->tierFor($manager);
        $config = config("tiers.{$tierKey}");

        return [
            'tier'        => $tierKey,
            'tier_label'  => $config['label'],
            'price_label' => $config['price_label'],
            'tier_until'  => $manager->tier_until?->toDateString(),
            'usage' => [
                'active_leagues' => [
                    'used'  => $this->activeLeagueCount($manager),
                    'limit' => $config['limits']['active_leagues'],
                ],
            ],
        ];
    }

    public function leagueSnapshot(League $league): array
    {
        $manager = $league->manager;
        $tierKey = $this->tierFor($manager);
        $config = config("tiers.{$tierKey}");

        return [
            'players' => [
                'used'  => $league->players()->count(),
                'limit' => $config['limits']['players_per_league'],
            ],
            'jornadas' => [
                'used'  => $league->groups()->withCount('jornadas')->get()->sum('jornadas_count'),
                'limit' => $config['limits']['jornadas_per_league'],
            ],
        ];
    }

    // ============================================================
    // Internals
    // ============================================================

    private function activeLeagueCount(Manager $manager): int
    {
        return $manager->leagues()
            ->whereIn('status', ['draft', 'active'])
            ->count();
    }
}
