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
        'movement',
        'window_start',
        'window_end',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'number'       => 'integer',
            'movement'     => 'integer',
            'window_start' => 'date',
            'window_end'   => 'date',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * How many competitors move up AND down from each cancha of THIS jornada.
     * A per-jornada override (the `movement` column) wins; otherwise we fall back
     * to the league's global parity rule. Always ≥ 1.
     */
    public function effectiveMovement(): int
    {
        return $this->group->league->movementForJornada($this);
    }

    public function canchas(): HasMany
    {
        return $this->hasMany(Cancha::class)->orderBy('position');
    }
    /**
     * A jornada is editable only if it is the latest (highest-numbered) jornada
     * in its group. Once a later jornada exists, this one is frozen — because the
     * later jornada's canchas were generated from this one's results, so editing
     * it now would corrupt the promotion/relegation chain.
     */
    public function isEditable(): bool
    {
        return !$this->group->jornadas()
            ->where('number', '>', $this->number)
            ->exists();
    }

    /** Inverse, for readable guard code. */
    public function isFrozen(): bool
    {
        return !$this->isEditable();
    }

    /** Is this the latest jornada in its group? (Only the latest can be deleted.) */
    public function isLatest(): bool
    {
        return !$this->group->jornadas()
            ->where('number', '>', $this->number)
            ->exists();
    }
}
