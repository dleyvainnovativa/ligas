<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerRequest;
use App\Models\League;
use App\Models\Player;
use App\Services\PlayerImportService;
use App\Services\RosterService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    public function __construct(private PlayerImportService $importer, private RosterService $roster) {}

    public function index(Request $request, League $league)
    {
        $this->authorize('view', $league);
        $players = $league->players()->orderBy('full_name')->get();

        return view('leagues.players.index', compact('league', 'players'));
    }

    public function store(PlayerRequest $request, League $league, \App\Services\TierService $tiers)
    {
        $this->authorize('update', $league);
        // dd($league->groups()->first());
        if (!$tiers->canAddPlayer($league)) {
            $snapshot = $tiers->leagueSnapshot($league);
            $limit = $snapshot['players']['limit'];
            return response()->json([
                'message' => "Esta liga llegó al límite de {$limit} jugadores. Mejora tu plan para agregar más.",
            ], 422);
        }
        $data = $request->validated();
        $data['paid_amount']    = $data['paid_amount'] ?? 0;
        $data['payment_status'] = $data['payment_status']
            ?? $this->importer->derivePaymentStatus((float) $data['paid_amount'], (float) $league->cost);

        $player = $league->players()->create($data);
        $group = $league->groups()->first();
        $assigned = $league->format === League::FORMAT_PAIRS
            ? $this->roster->autoFillPairs($group, 1)
            : $this->roster->autoFillPlayers($group, 1);

        return response()->json(['player' => $this->serialize($player)]);
    }

    public function update(PlayerRequest $request, League $league, Player $player)
    {
        $this->authorize('update', $player);
        abort_unless($player->league_id === $league->id, 404);

        $data = $request->validated();
        if (isset($data['paid_amount']) && !isset($data['payment_status'])) {
            $data['payment_status'] = $this->importer->derivePaymentStatus(
                (float) $data['paid_amount'],
                (float) $league->cost
            );
        }

        $player->update($data);
        return response()->json(['player' => $this->serialize($player->fresh())]);
    }

    public function destroy(League $league, Player $player)
    {
        $this->authorize('delete', $player);
        abort_unless($player->league_id === $league->id, 404);

        $player->delete();
        return response()->json(['ok' => true]);
    }

    public function importPreview(Request $request, League $league)
    {
        $this->authorize('update', $league);
        // $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        return response()->json($this->importer->preview($request->file('file')));
    }

    public function import(
        Request $request,
        League $league,
        \App\Services\TierService $tiers
    ) {
        $this->authorize('update', $league);
        $request->validate([
            'file' => ['required', 'file', 'max:5120', function ($attr, $value, $fail) {
                $ext = strtolower($value->getClientOriginalExtension());
                if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls'], true)) {
                    $fail('El archivo debe ser CSV o Excel (.xlsx, .xls).');
                }
            }],
        ]);

        // Parse without saving to count how many players we'd be adding
        $preview = $this->importer->preview($request->file('file'));
        $incomingCount = $preview['valid'] ?? 0;
        // dd($incomingCount, $preview);

        if ($incomingCount === 0) {
            return response()->json([
                'message' => 'El archivo no contiene jugadores válidos para importar.',
            ], 422);
        }

        // Tier check: would importing this many exceed the league's player limit?
        if (!$tiers->canAddPlayer($league, $incomingCount)) {
            $snapshot = $tiers->leagueSnapshot($league);
            $used      = $snapshot['players']['used'];
            $limit     = $snapshot['players']['limit'];
            $available = max(0, $limit - $used);

            return response()->json([
                'message' => $available === 0
                    ? "Esta liga ya llegó al límite de {$limit} jugadores. Mejora tu plan para agregar más."
                    : "Estás intentando importar {$incomingCount} jugadores pero tu plan solo permite agregar {$available} más en esta liga (tienes {$used}/{$limit}).",
            ], 422);
        }

        $group = $league->groups()->first();
        $result = $this->importer->import($league, $request->file('file'));

        // Auto-assign newly-imported players into the first group, preserving original behavior
        if ($group) {
            $assigned = $league->format === League::FORMAT_PAIRS
                ? $this->roster->autoFillPairs($group, 2000)
                : $this->roster->autoFillPlayers($group, 2000);
        }

        return response()->json($result);
    }

    private function serialize(Player $p): array
    {
        return [
            'id'             => $p->id,
            'full_name'      => $p->full_name,
            'email'          => $p->email,
            'phone'          => $p->phone,
            'paid_amount'    => (float) $p->paid_amount,
            'payment_status' => $p->payment_status,
            'notes'          => $p->notes,
        ];
    }
}
