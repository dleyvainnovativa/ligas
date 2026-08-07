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
            'globalAds' => Ad::whereNull('league_id')->orderBy('position')->get(),
            'leagueAds' => Ad::whereNotNull('league_id')->with('league')->orderBy('league_id')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.ads.create', ['leagues' => League::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'scope'     => 'required|in:global,league',
            'league_id' => 'required_if:scope,league|nullable|exists:leagues,id',
            'title'     => 'nullable|string|max:255',
            'link_url'  => 'nullable|url|max:255',
            'image'     => 'required|image|max:2048',
            'position'  => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $path = $request->file('image')->store('ads', 'public');

        Ad::create([
            'league_id' => $data['scope'] === 'global' ? null : $data['league_id'],
            'image_path' => $path,
            'title'     => $data['title'] ?? null,
            'link_url'  => $data['link_url'] ?? null,
            'position'  => $data['position'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.ads.index')->with('status', 'Anuncio creado.');
    }

    public function edit(Ad $ad)
    {
        return view('admin.ads.edit', ['ad' => $ad, 'leagues' => League::orderBy('name')->get()]);
    }

    public function update(Request $request, Ad $ad)
    {
        $data = $request->validate([
            'scope'     => 'required|in:global,league',
            'league_id' => 'required_if:scope,league|nullable|exists:leagues,id',
            'title'     => 'nullable|string|max:255',
            'link_url'  => 'nullable|url|max:255',
            'image'     => 'nullable|image|max:2048',
            'position'  => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('ads', 'public');
        }
        $data['league_id'] = $data['scope'] === 'global' ? null : $data['league_id'];
        $data['is_active'] = $request->boolean('is_active', true);
        unset($data['scope'], $data['image']);

        $ad->update($data);
        return redirect()->route('admin.ads.index')->with('status', 'Anuncio actualizado.');
    }

    public function destroy(Ad $ad)
    {
        $ad->delete();
        return back()->with('status', 'Anuncio eliminado.');
    }
}
