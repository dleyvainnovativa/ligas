<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Services\CanchaService;
use Illuminate\Http\Request;

class JornadaController extends Controller
{
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

        $jornada->delete();
        return response()->json(['ok' => true]);
    }

    public function autoFill(League $league, Group $group, Jornada $jornada)
    {
        $this->authorize('update', $jornada);
        abort_unless($jornada->group_id === $group->id && $group->league_id === $league->id, 404);

        $this->canchas->autoFill($jornada);
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
