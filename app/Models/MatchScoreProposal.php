<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchScoreProposal extends Model
{
    public const STATUS_PENDING    = 'pending';
    public const STATUS_ACCEPTED   = 'accepted';
    public const STATUS_MODIFIED   = 'modified';
    public const STATUS_REJECTED   = 'rejected';
    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'match_id',
        'sets',
        'proposer_name',
        'proposer_token',
        'ip',
        'user_agent',
        'status',
        'superseded_by_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'sets'        => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
