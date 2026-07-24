<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Services\CanchaService;
use Illuminate\Http\Request;
use App\Http\Controllers\Concerns\GuardsFrozenJornadas;

class JornadaController extends Controller
{
    use GuardsFrozenJornadas;

    public function __construct(private CanchaService $canchas) {}

    public function index(League $league, Group $group)
    {
        $this->authorize('view', $league);
        abort_unless($group->league_id === $league->id, 404);

        $group->load('jornadas');

        return view('leagues.jornadas.index', compact('league', 'group'));
    }

    public function show(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('view', $jornada);
        abort_unless(
            $jornada->group_id === $group->id && $group->league_id === $league->id,
            404
        );

        $jornada->load([
            'canchas.players',
            'canchas.pairs.playerA',
            'canchas.pairs.playerB',
            'group.league',
        ]);

        return view('leagues.jornadas.show', [
            'league'  => $league,
            'group'   => $group,
            'jornada' => $jornada,
        ]);
    }

    public function store(
        Request $request,
        League $league,
        Group $group,
        \App\Services\TierService $tiers,
        \App\Services\PromotionRelegationService $promo,
        \App\Services\CanchaService $canchaService
    ) {
        $this->authorize('update', $group);
        abort_unless($group->league_id === $league->id, 404);

        // ---- Tier limit on jornadas ----
        if (!$tiers->canAddJornada($league)) {
            $snapshot = $tiers->leagueSnapshot($league);
            return response()->json([
                'message' => "Esta liga llegó al límite de {$snapshot['jornadas']['limit']} jornadas. Mejora tu plan para agregar más.",
            ], 422);
        }

        // ---- Determine the new jornada number ----
        $lastNumber = $group->jornadas()->max('number') ?? 0;
        $newNumber = $lastNumber + 1;

        // ---- Item 3: gate — previous jornada must be complete (unless this is #1) ----
        if ($newNumber > 1) {
            $previous = $group->jornadas()->where('number', $lastNumber)->first();

            if (!$previous || !$this->isJornadaComplete($previous)) {
                return response()->json([
                    'message' => "Debes completar todos los resultados de la Jornada {$lastNumber} antes de crear la Jornada {$newNumber}.",
                ], 422);
            }
        }

        // ---- Create the jornada ----
        $jornada = $group->jornadas()->create([
            'number' => $newNumber,
            'status' => 'draft',
        ]);

        // ---- Item 4: auto-generate canchas from previous results (if not first) ----
        $generated = false;
        if ($newNumber > 1) {
            $previous = $group->jornadas()->where('number', $lastNumber)->first();
            $distribution = $promo->computeNextDistribution(
                $previous,
                (int) $league->promotion_relegation
            );

            if (!empty($distribution)) {
                $this->buildCanchasFromDistribution($jornada, $distribution, $canchaService);
                $generated = true;
            }
        }

        return response()->json([
            'jornada'   => [
                'id'     => $jornada->id,
                'number' => $jornada->number,
                'status' => $jornada->status,
            ],
            'generated' => $generated,
            'message'   => $generated
                ? "Jornada {$newNumber} creada con canchas generadas desde los resultados anteriores. Revísalas y ajústalas si es necesario."
                : "Jornada {$newNumber} creada.",
        ]);
    }

    /**
     * A jornada is "complete" when it has at least one cancha and every cancha
     * is marked completed.
     */
    private function isJornadaComplete(Jornada $jornada): bool
    {
        $canchas = $jornada->canchas()->get();
        if ($canchas->isEmpty()) return false;
        return $canchas->every(fn($c) => $c->status === \App\Models\Cancha::STATUS_COMPLETED);
    }

    /**
     * Create canchas for the new jornada and assign players per the distribution.
     * Canchas are created unscheduled; the manager schedules them afterward.
     */
    private function buildCanchasFromDistribution(
        Jornada $jornada,
        array $distribution,
        \App\Services\CanchaService $canchaService
    ): void {
        foreach ($distribution as $index => $playerIds) {
            if (empty($playerIds)) continue;

            $cancha = $jornada->canchas()->create([
                'label'    => 'Cancha ' . ($index + 1),
                'position' => $index + 1,
                'status'   => \App\Models\Cancha::STATUS_UNSCHEDULED,
            ]);

            // Attach players with slot numbers 1..N
            $slot = 1;
            foreach ($playerIds as $pid) {
                $cancha->players()->attach($pid, ['slot' => $slot]);
                $slot++;
            }
        }
    }

    public function update(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);
        $this->ensureJornadaEditable($jornada);
        $data = $request->validate([
            'window_start' => ['nullable', 'date'],
            'window_end'   => ['nullable', 'date', 'after_or_equal:window_start'],
            'notes'        => ['nullable', 'string', 'max:500'],
            'status'       => ['nullable', 'in:draft,scheduled,completed'],
        ]);

        $jornada->update($data);
        return response()->json(['jornada' => $this->serialize($jornada->fresh())]);
    }

    public function destroy(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('delete', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        // Only the latest jornada in the group can be deleted. This is the escape
        // hatch: to unlock a frozen jornada, peel off the ones after it, one at a time.
        if (!$jornada->isLatest()) {
            return response()->json([
                'message' => "Solo puedes eliminar la última jornada del grupo. "
                    . "Elimina las jornadas posteriores primero.",
                'code'    => 'JORNADA_NOT_LATEST',
            ], 422);
        }

        // Cascade: delete canchas → rounds → proposals so nothing is orphaned.
        $this->cascadeDeleteJornada($jornada);

        app(\App\Services\PublicCacheService::class)->bust($league);

        return response()->json([
            'ok' => true,
            'message' => "Jornada {$jornada->number} eliminada. La jornada anterior vuelve a estar disponible para editar.",
        ]);
    }

    /**
     * Remove a jornada and everything under it: canchas, their rounds (game_matches),
     * and any score proposals attached to those rounds.
     */
    private function cascadeDeleteJornada(Jornada $jornada): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($jornada) {
            $canchas = $jornada->canchas()->with('rounds')->get();

            foreach ($canchas as $cancha) {
                $roundIds = $cancha->rounds->pluck('id');

                if ($roundIds->isNotEmpty()) {
                    // Proposals reference rounds via match_id
                    \App\Models\MatchScoreProposal::whereIn('match_id', $roundIds)->delete();
                    // Rounds themselves
                    \App\Models\GameMatch::whereIn('id', $roundIds)->delete();
                }

                // Pivot rows (cancha_player / cancha_pair) — detach then delete cancha
                $cancha->players()->detach();
                $cancha->pairs()->detach();
                $cancha->delete();
            }

            $jornada->delete();
        });
    }

    public function autoFill(Request $request, League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);
        $this->ensureJornadaEditable($jornada);

        $confirm = (bool) $request->input('confirm_reset', false);

        // Any scheduled/completed canchas that auto-fill would invalidate?
        $affected = $jornada->canchas()->get()
            ->filter(fn($c) => $this->canchas->canchaHasScheduleOrResults($c))
            ->values();

        if ($affected->isNotEmpty() && !$confirm) {
            $withResults = $affected->filter(fn($c) => $c->rounds()->whereNotNull('sets')->exists());
            $msg = "Auto-generar reasignará a todos los jugadores y reprogramará {$affected->count()} cancha(s)";
            if ($withResults->isNotEmpty()) {
                $msg .= " ({$withResults->count()} con resultados que se borrarán)";
            }
            $msg .= ". ¿Continuar?";

            return response()->json([
                'needs_confirmation' => true,
                'message' => $msg,
            ], 422);
        }

        // Reset schedules/results on affected canchas, then auto-fill assignments
        \Illuminate\Support\Facades\DB::transaction(function () use ($affected, $jornada) {
            foreach ($affected as $c) {
                $this->canchas->resetCanchaScheduleAndResults($c);
            }
            $this->canchas->autoFill($jornada);

            // Every cancha's roster changed — rebuild all rounds
            $scheduler = app(\App\Services\MatchSchedulingService::class);
            foreach ($jornada->canchas()->get() as $c) {
                $scheduler->rebuildRounds($c);
            }
        });

        app(\App\Services\PublicCacheService::class)->bust($league);

        return response()->json(['ok' => true]);
    }

    private function serialize(Jornada $j): array
    {
        return [
            'id'           => $j->id,
            'number'       => $j->number,
            'status'       => $j->status,
            'window_start' => $j->window_start?->toDateString(),
            'window_end'   => $j->window_end?->toDateString(),
            'canchas_count' => $j->canchas()->count(),
        ];
    }
    public function standings(
        League $league,
        Group $group,
        Jornada $jornada,
        \App\Services\PromotionRelegationService $promo
    ) {
        $this->authorize('view', $group);
        abort_unless(
            $jornada->group_id === $group->id && $group->league_id === $league->id,
            404
        );

        $breakdown = $promo->jornadaBreakdown($jornada, (int) $league->promotion_relegation);
        $playerNames = $league->players()->pluck('full_name', 'id');
        $isComplete = $this->isJornadaComplete($jornada);

        return view('leagues.jornadas.standings', [
            'league'      => $league,
            'group'       => $group,
            'jornada'     => $jornada,
            'breakdown'   => $breakdown,
            'playerNames' => $playerNames,
            'isComplete'  => $isComplete,
        ]);
    }
}
