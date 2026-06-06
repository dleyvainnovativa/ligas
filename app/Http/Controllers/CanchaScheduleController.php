<?php

namespace App\Http\Controllers;

use App\Models\Cancha;
use App\Models\Group;
use App\Models\Jornada;
use App\Models\League;
use App\Services\MatchSchedulingService;
use Illuminate\Http\Request;

class CanchaScheduleController extends Controller
{
    public function __construct(private MatchSchedulingService $scheduler) {}

    public function schedule(Request $request, League $league, Group $group, Jornada $jornada, Cancha $cancha)
    {
        $this->authorize('update', $cancha);
        abort_unless($cancha->jornada_id === $jornada->id, 404);

        $data = $request->validate([
            'date'      => ['nullable', 'date'],
            'time_slot' => ['nullable', 'regex:/^([01]\d|2[0-3]):[0-5]\d$/'],
            'pista_id'  => ['nullable', 'integer'],
        ]);

        $updated = $this->scheduler->scheduleCancha(
            $cancha,
            $data['date'] ?? null,
            $data['time_slot'] ?? null,
            $data['pista_id'] ?? null,
        );

        return response()->json(['cancha' => $this->serialize($updated)]);
    }

    private function serialize(Cancha $c): array
    {
        return [
            'id'        => $c->id,
            'label'     => $c->label,
            'date'      => $c->date?->toDateString(),
            'time_slot' => $c->time_slot,
            'pista_id'  => $c->pista_id,
            'status'    => $c->status,
        ];
    }
}
