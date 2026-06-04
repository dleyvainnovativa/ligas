<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdRequest;
use App\Models\Ad;
use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdController extends Controller
{
    public function __construct(private \App\Services\ImageProcessor $images) {}

    public function index(League $league)
    {
        $this->authorize('view', $league);
        $league->load('ads');
        return view('leagues.ads.index', compact('league'));
    }

    public function store(AdRequest $request, League $league)
    {
        $this->authorize('update', $league);

        $data = $request->validated();
        $data['image_path'] = $this->images->storeResized($request->file('image'), 'ads', 1600, 533);

        $data['position']   = ($league->ads()->max('position') ?? 0) + 1;
        $data['is_active']  = $data['is_active'] ?? true;
        unset($data['image']);

        $ad = $league->ads()->create($data);
        return response()->json(['ad' => $this->serialize($ad)]);
    }

    public function update(AdRequest $request, League $league, Ad $ad)
    {
        $this->authorize('update', $ad);
        abort_unless($ad->league_id === $league->id, 404);

        $data = $request->validated();

        if ($request->hasFile('image')) {
            if ($ad->image_path) Storage::disk('public')->delete($ad->image_path);
            $data['image_path'] = $this->images->storeResized($request->file('image'), 'ads', 1600, 533);
        }
        unset($data['image']);

        $ad->update($data);
        return response()->json(['ad' => $this->serialize($ad->fresh())]);
    }

    public function destroy(League $league, Ad $ad)
    {
        $this->authorize('delete', $ad);
        abort_unless($ad->league_id === $league->id, 404);

        if ($ad->image_path) Storage::disk('public')->delete($ad->image_path);
        $ad->delete();
        return response()->json(['ok' => true]);
    }

    public function reorder(Request $request, League $league)
    {
        $this->authorize('update', $league);
        $data = $request->validate([
            'ids'   => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        foreach ($data['ids'] as $i => $id) {
            $league->ads()->where('id', $id)->update(['position' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }

    private function serialize(Ad $a): array
    {
        return [
            'id'        => $a->id,
            'title'     => $a->title,
            'link_url'  => $a->link_url,
            'is_active' => $a->is_active,
            'position'  => $a->position,
            'image_url' => $a->image_url,
        ];
    }
}
