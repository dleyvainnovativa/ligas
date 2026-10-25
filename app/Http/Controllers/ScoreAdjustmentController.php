<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\ScoreAdjustment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScoreAdjustmentController extends Controller
{
    public function index(League $league)
    {
        $this->authorize('view', $league);

        $isPairs = $league->format === League::FORMAT_PAIRS;

        $adjustments = $league->scoreAdjustments()
            ->with(['player', 'pair.playerA', 'pair.playerB'])
            ->get();

        $players = $isPairs
            ? collect()
            : $league->players()->get();

        $pairs = $isPairs
            ? $league->pairs()->with(['playerA', 'playerB'])->get()
            : collect();

        return view('leagues.adjustments.index', [
            'league'      => $league,
            'isPairs'     => $isPairs,
            'adjustments' => $adjustments,
            'players'     => $players,
            'pairs'       => $pairs,
        ]);
    }

    public function store(Request $request, League $league)
    {
        $this->authorize('update', $league);

        $isPairs = $league->format === League::FORMAT_PAIRS;

        $data = $request->validate([
            'player_id' => [
                'nullable', 'integer',
                Rule::exists('players', 'id')->where('league_id', $league->id),
            ],
            'pair_id' => [
                'nullable', 'integer',
                Rule::exists('pairs', 'id')->where('league_id', $league->id),
            ],
            'unit'   => ['required', Rule::in(ScoreAdjustment::UNITS)],
            'amount' => ['required', 'integer', 'between:-999,999', 'not_in:0'],
            'note'   => ['nullable', 'string', 'max:255'],
        ]);

        $hasPlayer = !empty($data['player_id']);
        $hasPair   = !empty($data['pair_id']);

        // Exactly one subject, and it must match the league format.
        if ($hasPlayer === $hasPair) {
            throw ValidationException::withMessages([
                'subject' => 'Elige un jugador o una pareja.',
            ]);
        }
        if ($isPairs && $hasPlayer) {
            throw ValidationException::withMessages([
                'subject' => 'Esta liga es por parejas: elige una pareja.',
            ]);
        }
        if (!$isPairs && $hasPair) {
            throw ValidationException::withMessages([
                'subject' => 'Esta liga es individual: elige un jugador.',
            ]);
        }

        $adjustment = $league->scoreAdjustments()->create([
            'player_id' => $data['player_id'] ?? null,
            'pair_id'   => $data['pair_id'] ?? null,
            'unit'      => $data['unit'],
            'amount'    => $data['amount'],
            'note'      => $data['note'] ?? null,
        ]);

        app(\App\Services\PublicCacheService::class)->bust($league);

        return response()->json([
            'adjustment' => $this->serialize($adjustment->fresh(['player', 'pair.playerA', 'pair.playerB'])),
            'message'    => 'Puntos extra agregados.',
        ]);
    }

    public function destroy(League $league, ScoreAdjustment $adjustment)
    {
        $this->authorize('update', $league);
        abort_unless($adjustment->league_id === $league->id, 404);

        $adjustment->delete();

        app(\App\Services\PublicCacheService::class)->bust($league);

        return response()->json(['ok' => true, 'message' => 'Puntos extra eliminados.']);
    }

    private function serialize(ScoreAdjustment $a): array
    {
        return [
            'id'      => $a->id,
            'subject' => $a->subject_name,
            'unit'    => $a->unit,
            'amount'  => $a->amount,
            'note'    => $a->note,
        ];
    }
}
