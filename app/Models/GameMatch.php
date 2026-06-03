<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    protected $table = 'game_matches';

    public const STATUS_UNSCHEDULED = 'unscheduled';
    public const STATUS_SCHEDULED   = 'scheduled';
    public const STATUS_COMPLETED   = 'completed';

    protected $fillable = [
        'cancha_id',
        'rotation_index',
        'date',
        'time_slot',
        'pista_id',
        'team_a_player_ids',
        'team_b_player_ids',
        'team_a_pair_id',
        'team_b_pair_id',
        'sets',
        'winner',
        'played_at',
        'no_show_player_ids',
        'suplente_player_ids',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'team_a_player_ids'   => 'array',
            'team_b_player_ids'   => 'array',
            'sets'                => 'array',
            'no_show_player_ids'  => 'array',
            'suplente_player_ids' => 'array',
            'date'                => 'date',
            'played_at'           => 'datetime',
            'rotation_index'      => 'integer',
        ];
    }

    public function cancha(): BelongsTo
    {
        return $this->belongsTo(Cancha::class);
    }

    public function pista(): BelongsTo
    {
        return $this->belongsTo(Pista::class);
    }
    /**
     * Compute per-side games won and sets won from the stored sets array.
     * sets format: [[6,4],[3,6],[7,5]] meaning side A then B per set.
     *
     * Returns ['games_a' => int, 'games_b' => int, 'sets_a' => int, 'sets_b' => int]
     */
    public function tally(): array
    {
        $tally = ['games_a' => 0, 'games_b' => 0, 'sets_a' => 0, 'sets_b' => 0];
        foreach (($this->sets ?? []) as $set) {
            if (!is_array($set) || count($set) !== 2) continue;
            [$a, $b] = [(int) $set[0], (int) $set[1]];
            $tally['games_a'] += $a;
            $tally['games_b'] += $b;
            if ($a > $b)      $tally['sets_a']++;
            elseif ($b > $a)  $tally['sets_b']++;
        }
        return $tally;
    }

    public function deriveWinner(): ?string
    {
        $t = $this->tally();
        if ($t['sets_a'] === 0 && $t['sets_b'] === 0) return null;
        if ($t['sets_a'] > $t['sets_b']) return 'a';
        if ($t['sets_b'] > $t['sets_a']) return 'b';
        return 'draw';
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(MatchScoreProposal::class, 'match_id');
    }

    public function pendingProposal()
    {
        return $this->hasOne(MatchScoreProposal::class, 'match_id')
            ->where('status', MatchScoreProposal::STATUS_PENDING)
            ->latest('id');
    }
}
