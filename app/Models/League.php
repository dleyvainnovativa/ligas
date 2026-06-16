<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    use HasFactory;

    public const FORMAT_INDIVIDUAL = 'individual';
    public const FORMAT_PAIRS      = 'pairs';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_ACTIVE    = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ARCHIVED  = 'archived';

    public const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

    protected static function booted()
    {
        static::deleting(function (League $league) {
            if ($league->banner_path) {
                Storage::disk('public')->delete($league->banner_path);
            }
            // Ads deleted via cascade; clean their files
            foreach ($league->ads as $ad) {
                if ($ad->image_path) {
                    Storage::disk('public')->delete($ad->image_path);
                }
            }
        });
    }

    protected $fillable = [
        'manager_id',
        'name',
        'slug',
        'description',
        'banner_path',
        'format',
        'num_jornadas',
        'cost',
        'days_of_week',
        'time_slots',
        'penalty_suplente',
        'penalty_no_show',
        'jornadas_pares',
        'jornadas_nones',
        'status',
        'points_win',
        'points_draw',
        'points_loss',
        'whatsapp_url',
        'promotion_relegation',
    ];

    protected function casts(): array
    {
        return [
            'days_of_week'     => 'array',
            'time_slots'       => 'array',
            'cost'             => 'decimal:2',
            'num_jornadas'     => 'integer',
            'penalty_suplente' => 'integer',
            'penalty_no_show'  => 'integer',
            'jornadas_pares'   => 'integer',
            'jornadas_nones'   => 'integer',
            'points_win'  => 'integer',
            'points_draw' => 'integer',
            'points_loss' => 'integer',
            'promotion_relegation'   => 'integer',
        ];
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Manager::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return $this->banner_path
            ? asset("storage/$this->banner_path")
            // ? Storage::disk('public')->url($this->banner_path)
            : null;
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/' . $this->slug);
    }
    public function sedes(): HasMany
    {
        return $this->hasMany(Sede::class)->orderBy('position');
    }

    public function players(): HasMany
    {
        return $this->hasMany(Player::class)->orderBy('full_name');
    }

    public function pistas()
    {
        return $this->hasManyThrough(Pista::class, Sede::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class)->orderBy('position');
    }

    public function pairs(): HasMany
    {
        return $this->hasMany(Pair::class);
    }
    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class)->orderBy('position');
    }

    public function activeAds(): HasMany
    {
        return $this->hasMany(Ad::class)->where('is_active', true)->orderBy('position');
    }
}
