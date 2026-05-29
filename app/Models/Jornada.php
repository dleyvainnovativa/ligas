<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jornada extends Model
{
    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'group_id',
        'number',
        'status',
        'window_start',
        'window_end',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'number'       => 'integer',
            'window_start' => 'date',
            'window_end'   => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function canchas(): HasMany
    {
        return $this->hasMany(Cancha::class)->orderBy('position');
    }
}
