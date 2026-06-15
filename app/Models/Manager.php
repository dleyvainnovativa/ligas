<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manager extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $fillable = [
        'firebase_uid',
        'email',
        'name',
        'avatar_url',
        'remember_token',
        'last_login_at',
        'tier',
        'tier_until',     // ← new
    ];

    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
            'tier_until'    => 'datetime',
        ];
    }

    public function leagues(): HasMany
    {
        return $this->hasMany(League::class);
    }
}
