<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Player extends Model
{
    public const STATUS_UNPAID  = 'unpaid';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID    = 'paid';

    protected $fillable = [
        'league_id',
        'full_name',
        'email',
        'phone',
        'paid_amount',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_amount' => 'decimal:2',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }


    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(Group::class, 'group_player')
            ->withPivot(['joined_at', 'left_at'])
            ->withTimestamps();
    }

    public function currentGroup()
    {
        return $this->groups()->wherePivotNull('left_at')->first();
    }
}
