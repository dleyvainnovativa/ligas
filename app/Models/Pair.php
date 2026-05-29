<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pair extends Model
{
    protected $fillable = ['league_id', 'player_a_id', 'player_b_id', 'label'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function playerA(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_a_id');
    }

    public function playerB(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player_b_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->label) return $this->label;
        $a = $this->playerA?->full_name ?? '?';
        $b = $this->playerB?->full_name ?? '?';
        return "{$a} / {$b}";
    }
}
