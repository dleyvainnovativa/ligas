<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A season-level extra-points adjustment for a player (individual leagues) or a
 * pair (doubles leagues). Applied ONLY to the cumulative general leaderboard
 * (PromotionRelegationService::groupSeasonStandings) — never to the per-cancha
 * promotion/relegation math, so bonuses can't change who moves up or down.
 */
class ScoreAdjustment extends Model
{
    public const UNIT_GAME = 'game';
    public const UNIT_SET  = 'set';

    public const UNITS = [self::UNIT_GAME, self::UNIT_SET];

    protected $fillable = [
        'league_id',
        'player_id',
        'pair_id',
        'unit',
        'amount',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function pair(): BelongsTo
    {
        return $this->belongsTo(Pair::class);
    }

    public function getSubjectNameAttribute(): string
    {
        return $this->player?->full_name
            ?? $this->pair?->display_name
            ?? '—';
    }
}
