<?php

namespace App\Http\Controllers;

use App\Http\Requests\PairRequest;
use App\Models\League;
use App\Models\Pair;
use Illuminate\Http\Request;
use App\Services\PlayerImportService;
use App\Services\RosterService;
use Illuminate\Support\Facades\Log;

class PairController extends Controller
{
    public function __construct(private PlayerImportService $importer, private RosterService $roster) {}

    public function store(PairRequest $request, League $league)
    {
        $this->authorize('update', $league);
        abort_unless($league->format === League::FORMAT_PAIRS, 422, 'La liga no es por parejas.');

        $data = $request->validated();

        // Validate both players belong to this league
        $playerIds = $league->players()->whereIn('id', [$data['player_a_id'], $data['player_b_id']])->pluck('id')->all();
        abort_if(count($playerIds) !== 2, 422, 'Los jugadores deben pertenecer a esta liga.');

        // Validate neither player is already in a pair
        $existingA = $league->pairs()->where('player_a_id', $data['player_a_id'])->orWhere('player_b_id', $data['player_a_id'])->exists();
        $existingB = $league->pairs()->where('player_a_id', $data['player_b_id'])->orWhere('player_b_id', $data['player_b_id'])->exists();
        abort_if($existingA || $existingB, 422, 'Uno de los jugadores ya está en una pareja.');

        $pair = $league->pairs()->create($data)->load(['playerA', 'playerB']);
        $group = $league->groups()->first();

        $assigned = $league->format === League::FORMAT_PAIRS
            ? $this->roster->autoFillPairs($group, 1)
            : $this->roster->autoFillPlayers($group, 1);

        return response()->json(['pair' => $this->serialize($pair)]);
    }

    /**
     * Register a whole pair at once: create both players and the pair in a single
     * transaction. This is the primary doubles registration flow (two-input form),
     * replacing "add players, then pair them separately".
     */
    public function storeWithPlayers(
        Request $request,
        League $league,
        \App\Services\PlayerImportService $importer,
        \App\Services\TierService $tiers
    ) {
        $this->authorize('update', $league);
        abort_unless($league->format === League::FORMAT_PAIRS, 422, 'La liga no es por parejas.');

        $statuses = [
            \App\Models\Player::STATUS_UNPAID,
            \App\Models\Player::STATUS_PARTIAL,
            \App\Models\Player::STATUS_PAID,
        ];

        $data = $request->validate([
            'a.full_name'      => ['required', 'string', 'max:120'],
            'a.email'          => ['nullable', 'email', 'max:160'],
            'a.phone'          => ['nullable', 'string', 'max:40'],
            'a.paid_amount'    => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'a.payment_status' => ['nullable', \Illuminate\Validation\Rule::in($statuses)],
            'b.full_name'      => ['required', 'string', 'max:120'],
            'b.email'          => ['nullable', 'email', 'max:160'],
            'b.phone'          => ['nullable', 'string', 'max:40'],
            'b.paid_amount'    => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'b.payment_status' => ['nullable', \Illuminate\Validation\Rule::in($statuses)],
            'label'            => ['nullable', 'string', 'max:80'],
        ]);

        // Room for TWO more players under the plan limit.
        $snap  = $tiers->leagueSnapshot($league);
        $limit = $snap['players']['limit'] ?? null;
        $used  = $snap['players']['used'] ?? 0;
        if ($limit !== null && $used + 2 > $limit) {
            return response()->json([
                'message' => "No hay cupo para dos jugadores más en esta liga (límite {$limit}). Mejora tu plan.",
                'code'    => 'LEAGUE_LIMIT',
            ], 422);
        }

        $cost = (float) $league->cost;

        $pair = \Illuminate\Support\Facades\DB::transaction(function () use ($league, $data, $importer, $cost) {
            $makePlayer = function (array $p) use ($league, $importer, $cost) {
                $paid = (float) ($p['paid_amount'] ?? 0);
                return $league->players()->create([
                    'full_name'      => $p['full_name'],
                    'email'          => $p['email'] ?? null,
                    'phone'          => $p['phone'] ?? null,
                    'paid_amount'    => $paid,
                    'payment_status' => $p['payment_status'] ?? $importer->derivePaymentStatus($paid, $cost),
                ]);
            };

            $playerA = $makePlayer($data['a']);
            $playerB = $makePlayer($data['b']);

            return $league->pairs()->create([
                'player_a_id' => $playerA->id,
                'player_b_id' => $playerB->id,
                'label'       => $data['label'] ?? null,
            ])->load(['playerA', 'playerB']);
        });

        $group = $league->groups()->first();

        $assigned = $league->format === League::FORMAT_PAIRS
            ? $this->roster->autoFillPairs($group, 1)
            : $this->roster->autoFillPlayers($group, 1);

        Log::info("Auto-filled {$assigned} players/pairs into group {$group->id} of league {$league->id} after creating pair {$pair->id}.");


        return response()->json([
            'pair'    => $this->serialize($pair),
            'message' => 'Pareja registrada.',
        ]);
    }

    public function update(PairRequest $request, League $league, Pair $pair)
    {
        $this->authorize('update', $pair);
        abort_unless($pair->league_id === $league->id, 404);

        $pair->update(['label' => $request->validated()['label'] ?? null]);
        return response()->json(['pair' => $this->serialize($pair->fresh()->load(['playerA', 'playerB']))]);
    }

    public function destroy(League $league, Pair $pair)
    {
        $this->authorize('delete', $pair);
        abort_unless($pair->league_id === $league->id, 404);

        $pair->delete();
        return response()->json(['ok' => true]);
    }

    private function serialize(Pair $p): array
    {
        return [
            'id'           => $p->id,
            'label'        => $p->label,
            'display_name' => $p->display_name,
            'player_a'     => ['id' => $p->playerA?->id, 'full_name' => $p->playerA?->full_name],
            'player_b'     => ['id' => $p->playerB?->id, 'full_name' => $p->playerB?->full_name],
        ];
    }
}
