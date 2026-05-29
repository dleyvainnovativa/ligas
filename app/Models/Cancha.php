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

    protected $fillable = ['jornada_id', 'label', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
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

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class)->orderBy('rotation_index');
    }
}
