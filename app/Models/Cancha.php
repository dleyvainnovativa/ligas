<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cancha extends Model
{
    public const MAX_PLAYERS = 4;
    public const MAX_PAIRS   = 2;

    public const STATUS_UNSCHEDULED = 'unscheduled';
    public const STATUS_SCHEDULED   = 'scheduled';
    public const STATUS_COMPLETED   = 'completed';

    protected $fillable = [
        'jornada_id',
        'label',
        'position',
        'date',
        'time_slot',
        'pista_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'date'     => 'date',
        ];
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
    }

    public function pista(): BelongsTo
    {
        return $this->belongsTo(Pista::class);
    }

    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'cancha_player')
            ->withPivot('slot')
            ->orderBy('cancha_player.slot');
    }

    public function pairs(): BelongsToMany
    {
        return $this->belongsToMany(Pair::class, 'cancha_pair')
            ->withPivot('slot')
            ->orderBy('cancha_pair.slot');
    }

    /** "Rounds" of the cancha (was matches). Same DB table, different meaning. */
    public function rounds(): HasMany
    {
        return $this->hasMany(GameMatch::class)->orderBy('rotation_index');
    }

    /** Backward-compat alias. Some code still says ->matches. */
    public function matches(): HasMany
    {
        return $this->rounds();
    }

    public function isScheduled(): bool
    {
        return $this->date !== null && $this->time_slot !== null && $this->pista_id !== null;
    }
}
