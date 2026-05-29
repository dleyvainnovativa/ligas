<?php

namespace App\Services;

use App\Models\League;
use App\Models\Manager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeagueService
{
    public function create(Manager $manager, array $data, ?UploadedFile $banner): League
    {
        $data['manager_id'] = $manager->id;
        $data['slug']       = $this->ensureUniqueSlug($data['slug'] ?? Str::slug($data['name']));

        if ($banner) {
            $data['banner_path'] = $banner->store('banners', 'public');
        }

        return League::create($data);
    }

    public function update(League $league, array $data, ?UploadedFile $banner): League
    {
        if (isset($data['slug']) && $data['slug'] !== $league->slug) {
            $data['slug'] = $this->ensureUniqueSlug($data['slug'], $league->id);
        }

        if ($banner) {
            if ($league->banner_path) {
                Storage::disk('public')->delete($league->banner_path);
            }
            $data['banner_path'] = $banner->store('banners', 'public');
        }

        $league->update($data);
        return $league->fresh();
    }

    public function delete(League $league): void
    {
        if ($league->banner_path) {
            Storage::disk('public')->delete($league->banner_path);
        }
        $league->delete();
    }

    private function ensureUniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base);
        $candidate = $slug;
        $n = 2;

        $query = League::where('slug', $candidate);
        if ($ignoreId) $query->where('id', '!=', $ignoreId);

        while ($query->exists()) {
            $candidate = "{$slug}-{$n}";
            $n++;
            $query = League::where('slug', $candidate);
            if ($ignoreId) $query->where('id', '!=', $ignoreId);
        }
        return $candidate;
    }
}
