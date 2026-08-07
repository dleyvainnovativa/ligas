<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Services\LeagueTierService;
use Illuminate\Http\Request;

class AdminLeagueController extends Controller
{
    public function __construct(private LeagueTierService $leagueTiers) {}

    public function index(Request $request)
    {
        $search = $request->string('q')->toString();

        $leagues = League::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount(['players', 'groups'])
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.leagues.index', [
            'leagues' => $leagues,
            'search'  => $search,
        ]);
    }

    public function edit(League $league)
    {
        return view('admin.leagues.edit', [
            'league'   => $league,
            'snapshot' => $this->leagueTiers->snapshot($league),
        ]);
    }

    public function update(Request $request, League $league)
    {
        // Empty string in a limit field means "unlimited" → stored as null.
        // This is the admin-facing mirror of the null-means-∞ convention.
        $data = $request->validate([
            'max_players'  => 'nullable|integer|min:1',
            'max_jornadas' => 'nullable|integer|min:1',
            'max_groups'   => 'nullable|integer|min:1',
        ]);

        $league->update([
            'max_players'  => $data['max_players']  ?? null,
            'max_jornadas' => $data['max_jornadas'] ?? null,
            'max_groups'   => $data['max_groups']   ?? null,
        ]);

        return redirect()
            ->route('admin.leagues.edit', $league)
            ->with('success', "Límites de «{$league->name}» actualizados.");
    }
}
