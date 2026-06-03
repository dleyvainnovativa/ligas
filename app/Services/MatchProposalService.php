<?php

namespace App\Services;

use App\Models\GameMatch;
use App\Models\MatchScoreProposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MatchProposalService
{
    /**
     * Create a new proposal. Supersedes any existing pending one for this match.
     */
    public function propose(GameMatch $match, array $sets, string $name, Request $request): MatchScoreProposal
    {
        // Sanitize sets
        $sets = $this->cleanSets($sets);
        if (empty($sets)) {
            throw new \DomainException('Debes ingresar al menos un set válido.');
        }

        return DB::transaction(function () use ($match, $sets, $name, $request) {
            // Find token cookie or mint one
            $token = $request->cookie('pl_proposer') ?: (string) Str::uuid();

            // Supersede existing pending proposal
            $existing = MatchScoreProposal::where('match_id', $match->id)
                ->where('status', MatchScoreProposal::STATUS_PENDING)
                ->latest('id')
                ->first();

            $proposal = MatchScoreProposal::create([
                'match_id'       => $match->id,
                'sets'           => $sets,
                'proposer_name'  => mb_substr(trim($name), 0, 120),
                'proposer_token' => $token,
                'ip'             => $request->ip(),
                'user_agent'     => mb_substr((string) $request->userAgent(), 0, 255),
                'status'         => MatchScoreProposal::STATUS_PENDING,
            ]);

            if ($existing) {
                $existing->update([
                    'status'           => MatchScoreProposal::STATUS_SUPERSEDED,
                    'superseded_by_id' => $proposal->id,
                ]);
            }

            return $proposal;
        });
    }

    /** Mark a proposal as accepted (when manager saves matching result). */
    public function markAccepted(MatchScoreProposal $proposal): void
    {
        $proposal->update([
            'status'      => MatchScoreProposal::STATUS_ACCEPTED,
            'reviewed_at' => now(),
        ]);
    }

    /** Mark a proposal as modified (when manager saves a different result). */
    public function markModified(MatchScoreProposal $proposal): void
    {
        $proposal->update([
            'status'      => MatchScoreProposal::STATUS_MODIFIED,
            'reviewed_at' => now(),
        ]);
    }

    /** Reject a proposal without saving any result. */
    public function reject(MatchScoreProposal $proposal): void
    {
        $proposal->update([
            'status'      => MatchScoreProposal::STATUS_REJECTED,
            'reviewed_at' => now(),
        ]);
    }

    /** Count pending proposals for a league (for the manager sidebar badge). */
    public function pendingCountForLeague(int $leagueId): int
    {
        return MatchScoreProposal::where('status', MatchScoreProposal::STATUS_PENDING)
            ->whereIn('match_id', function ($q) use ($leagueId) {
                $q->select('game_matches.id')
                    ->from('game_matches')
                    ->join('canchas',  'canchas.id',  '=', 'game_matches.cancha_id')
                    ->join('jornadas', 'jornadas.id', '=', 'canchas.jornada_id')
                    ->join('groups',   'groups.id',   '=', 'jornadas.group_id')
                    ->where('groups.league_id', $leagueId);
            })
            ->count();
    }

    private function cleanSets(array $sets): array
    {
        return array_values(array_filter(array_map(function ($s) {
            if (!is_array($s) || count($s) !== 2) return null;
            $a = (int) $s[0];
            $b = (int) $s[1];
            if ($a < 0 || $b < 0) return null;
            if ($a === 0 && $b === 0) return null;
            return [$a, $b];
        }, $sets)));
    }
}
