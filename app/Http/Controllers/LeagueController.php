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
                // 'jornadas_pares'   => 2,
                // 'jornadas_nones'   => 1,
                'status'           => League::STATUS_ACTIVE,
                'promotion_relegation'   => 1,

            ]),
        ]);
    }

    public function reportPdf(
        League $league,
        \App\Services\LeagueReportService $reports
    ) {
        $this->authorize('view', $league);

        $data = $reports->build($league);

        // dd(asset('img/logo.jpg'), public_path('img/logo.jpg'));
        // return view('leagues.pdf.report', $data);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('leagues.pdf.report', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isRemoteEnabled'      => true,   // allows the banner/logo image
                'defaultFont'          => 'DejaVu Sans',  // has full accent support
                'isHtml5ParserEnabled' => true,
                'chroot'               => public_path(),
            ]);

        $filename = \Illuminate\Support\Str::slug($league->name) . '-resumen.pdf';

        return $pdf->download($filename);
    }

    public function store(LeagueRequest $request, \App\Services\TierService $tiers)

    {
        $this->authorize('create', League::class);

        if (!$tiers->canCreateLeague($request->user())) {
            $snapshot = $tiers->snapshot($request->user());
            $limit = $snapshot['usage']['active_leagues']['limit'];
            return redirect()
                ->route('leagues.index')
                ->with('flash_error', "Tu plan {$snapshot['tier_label']} permite hasta {$limit} ligas activas. Cierra una liga antes de crear otra o mejora tu plan.");
        }

        $league = $this->leagues->create(
            $request->user(),
            $request->validated(),
            $request->file('banner'),
        );

        $group = $league->groups()->create([
            'name'     => "Principal",
            'position' => 1
        ]);

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
