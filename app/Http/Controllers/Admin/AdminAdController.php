<?php
// app/Http/Controllers/Admin/AdminAdController.php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\League;
use Illuminate\Http\Request;

class AdminAdController extends Controller
{
    public function index()
    {
        return view('admin.ads.index', [
            'globalAds' => Ad::where('is_global', true)->orderBy('position')->get(),
            'leagueAds' => Ad::where('is_global', false)->with('leagues')->orderBy('position')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.ads.create', ['leagues' => League::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateAd($request, true);

        $path = $request->file('image')->store('ads', 'public');
        $isGlobal = $data['scope'] === 'global';

        $ad = Ad::create([
            'league_id'  => null,
            'is_global'  => $isGlobal,
            'image_path' => $path,
            'title'      => $data['title'] ?? null,
            'link_url'   => $data['link_url'] ?? null,
            'position'   => $data['position'] ?? 0,
            'is_active'  => $request->boolean('is_active', true),
        ]);

        $ad->leagues()->sync($isGlobal ? [] : ($data['league_ids'] ?? []));

        return redirect()->route('admin.ads.index')->with('status', 'Anuncio creado.');
    }

    public function edit(Ad $ad)
    {
        $ad->load('leagues');
        return view('admin.ads.edit', ['ad' => $ad, 'leagues' => League::orderBy('name')->get()]);
    }

    public function update(Request $request, Ad $ad)
    {
        $data = $this->validateAd($request, false);

        if ($request->hasFile('image')) {
            $ad->image_path = $request->file('image')->store('ads', 'public');
        }

        $isGlobal = $data['scope'] === 'global';

        $ad->fill([
            'is_global' => $isGlobal,
            'league_id' => null,
            'title'     => $data['title'] ?? null,
            'link_url'  => $data['link_url'] ?? null,
            'position'  => $data['position'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        $ad->leagues()->sync($isGlobal ? [] : ($data['league_ids'] ?? []));

        return redirect()->route('admin.ads.index')->with('status', 'Anuncio actualizado.');
    }

    public function destroy(Ad $ad)
    {
        $ad->delete();
        return back()->with('status', 'Anuncio eliminado.');
    }

    private function validateAd(Request $request, bool $imageRequired): array
    {
        return $request->validate([
            'scope'       => 'required|in:global,league',
            'league_ids'  => 'required_if:scope,league|array',
            'league_ids.*'=> 'integer|exists:leagues,id',
            'title'       => 'nullable|string|max:255',
            'link_url'    => 'nullable|url|max:255',
            'image'       => ($imageRequired ? 'required' : 'nullable') . '|image|max:2048',
            'position'    => 'nullable|integer|min:0',
            'is_active'   => 'boolean',
        ], [
            'league_ids.required_if' => 'Selecciona al menos una liga.',
        ]);
    }
}
