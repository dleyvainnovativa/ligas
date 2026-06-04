<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Ad extends Model
{
    protected $fillable = [
        'league_id',
        'image_path',
        'title',
        'link_url',
        'is_active',
        'position',
    ];

    protected static function booted()
    {
        static::deleting(function (Ad $ad) {
            if ($ad->image_path) {
                Storage::disk('public')->delete($ad->image_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'position'  => 'integer',
        ];
    }

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            // ? Storage::disk('public')->url($this->image_path)
            ? asset("storage/$this->image_path")

            : null;
    }
}
