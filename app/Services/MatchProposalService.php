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
    public function propose(GameMatch $match, array $sets, string $name, Request $request, array $noShow = [], array $suplente = []): MatchScoreProposal
    {
        // Sanitize sets
        $sets = $this->cleanSets($sets);
        if (empty($sets)) {
            throw new \DomainException('Debes ingresar al menos un set válido.');
        }
        $errors = \App\Support\SetScoreRule::validateSets($sets);
        if (!empty($errors)) {
            throw new \DomainException($errors[0]['message']);
        }

        // Only keep flags for players that actually belong to this match.
        $participants = $this->matchParticipantIds($match);
        $noShow   = array_values(array_intersect($participants, array_map('intval', $noShow)));
        $suplente = array_values(array_intersect($participants, array_map('intval', $suplente)));

        return DB::transaction(function () use ($match, $sets, $name, $request, $noShow, $suplente) {
            // Find token cookie or mint one
            $token = $request->cookie('pl_proposer') ?: (string) Str::uuid();

            // Supersede existing pending proposal
            $existing = MatchScoreProposal::where('match_id', $match->id)
                ->where('status', MatchScoreProposal::STATUS_PENDING)
                ->latest('id')
                ->first();

            $proposal = MatchScoreProposal::create([
                'match_id'            => $match->id,
                'sets'                => $sets,
                'no_show_player_ids'  => $noShow ?: null,
                'suplente_player_ids' => $suplente ?: null,
                'proposer_name'       => mb_substr(trim($name), 0, 120),
                'proposer_token'      => $token,
                'ip'                  => $request->ip(),
                'user_agent'          => mb_substr((string) $request->userAgent(), 0, 255),
                'status'              => MatchScoreProposal::STATUS_PENDING,
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

    /**
     * All player ids in this round's cancha (penalties are proposed at the
     * cancha level, so a proposer may flag any player in the cancha — not just
     * the two teams of the specific set being proposed).
     */
    private function matchParticipantIds(GameMatch $match): array
    {
        $cancha = $match->cancha;
        if ($cancha) {
            $ids = $cancha->players->isNotEmpty()
                ? $cancha->players->pluck('id')->all()
                : $cancha->pairs->flatMap(fn($p) => [$p->player_a_id, $p->player_b_id])->all();
            if (!empty($ids)) {
                return array_values(array_unique(array_map('intval', $ids)));
            }
        }

        // Fallback: just this round's two teams.
        $ids = [];
        foreach ((array) $match->team_a_player_ids as $id) $ids[] = (int) $id;
        foreach ((array) $match->team_b_player_ids as $id) $ids[] = (int) $id;
        return array_values(array_unique($ids));
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
