<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeagueRequest;
use App\Models\League;
use App\Services\LeagueService;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function __construct(private LeagueService $leagues) {}

    public function index(Request $request)
    {
        $leagues = $request->user()->leagues()
            ->latest()
            ->get();

        return view('leagues.index', compact('leagues'));
    }

    public function create()
    {
        $this->authorize('create', League::class);
        return view('leagues.create', [
            'league' => new League([
                'format'           => League::FORMAT_INDIVIDUAL,
                'num_jornadas'     => 8,
                'cost'             => 0,
                'days_of_week'     => ['tue', 'thu'],
                'time_slots'       => ['18:00', '19:00', '20:00'],
                'penalty_suplente' => 0,
                'penalty_no_show'  => 3,
                'jornadas_pares'   => 2,
                'jornadas_nones'   => 1,
                'status'           => League::STATUS_DRAFT,
            ]),
        ]);
    }

    public function store(LeagueRequest $request)
    {
        $this->authorize('create', League::class);

        $league = $this->leagues->create(
            $request->user(),
            $request->validated(),
            $request->file('banner'),
        );

        return redirect()
            ->route('leagues.edit', $league)
            ->with('success', 'Liga creada correctamente.');
    }

    public function show(League $league)
    {
        $this->authorize('view', $league);
        return view('leagues.show', compact('league'));
    }

    public function edit(League $league)
    {
        $this->authorize('update', $league);
        return view('leagues.edit', compact('league'));
    }

    public function update(LeagueRequest $request, League $league)
    {
        $this->authorize('update', $league);

        $league = $this->leagues->update(
            $league,
            $request->validated(),
            $request->file('banner'),
        );

        return redirect()
            ->route('leagues.edit', $league)
            ->with('success', 'Cambios guardados.');
    }

    public function destroy(League $league)
    {
        $this->authorize('delete', $league);
        $this->leagues->delete($league);

        return redirect()
            ->route('leagues.index')
            ->with('success', 'Liga eliminada.');
    }
}
