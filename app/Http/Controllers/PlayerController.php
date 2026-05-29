<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerRequest;
use App\Models\League;
use App\Models\Player;
use App\Services\PlayerImportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    public function __construct(private PlayerImportService $importer) {}

    public function index(Request $request, League $league)
    {
        $this->authorize('view', $league);
        $players = $league->players()->orderBy('full_name')->get();

        return view('leagues.players.index', compact('league', 'players'));
    }

    public function store(PlayerRequest $request, League $league)
    {
        $this->authorize('update', $league);

        $data = $request->validated();
        $data['paid_amount']    = $data['paid_amount'] ?? 0;
        $data['payment_status'] = $data['payment_status']
            ?? $this->importer->derivePaymentStatus((float) $data['paid_amount'], (float) $league->cost);

        $player = $league->players()->create($data);

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
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        return response()->json($this->importer->preview($request->file('file')));
    }

    public function import(Request $request, League $league)
    {
        $this->authorize('update', $league);
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:2048']]);

        $result = $this->importer->import($league, $request->file('file'));
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
