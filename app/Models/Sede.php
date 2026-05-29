<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sede extends Model
{
    protected $fillable = ['league_id', 'name', 'address', 'notes', 'position'];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function pistas(): HasMany
    {
        return $this->hasMany(Pista::class)->orderBy('position');
    }
}
