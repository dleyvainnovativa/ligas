<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Group extends Model
{
    protected $fillable = ['league_id', 'name', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    /** Current (active) players in this group. */
    public function players(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'group_player')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNull('left_at');
    }

    /** Historical view: all players who have ever been in this group. */
    public function allPlayers(): BelongsToMany
    {
        return $this->belongsToMany(Player::class, 'group_player')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function pairs(): BelongsToMany
    {
        return $this->belongsToMany(Pair::class, 'group_pair')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps()
            ->wherePivotNull('left_at');
    }

    public function allPairs(): BelongsToMany
    {
        return $this->belongsToMany(Pair::class, 'group_pair')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function jornadas(): HasMany
    {
        return $this->hasMany(Jornada::class)->orderBy('number');
    }
}
