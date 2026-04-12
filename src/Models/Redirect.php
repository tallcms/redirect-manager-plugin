<?php

namespace Tallcms\RedirectManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Redirect extends Model
{
    protected $table = 'tallcms_redirects';

    protected $fillable = [
        'source_path',
        'destination_url',
        'status_code',
        'is_active',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_hit_at' => 'datetime',
            'status_code' => 'integer',
            'hit_count' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        // Canonicalize source_path and compute hash on write
        static::creating(function (self $redirect) {
            $redirect->source_path = static::normalizePath($redirect->source_path);
            $redirect->source_path_hash = hash('sha256', $redirect->source_path);
        });

        static::updating(function (self $redirect) {
            if ($redirect->isDirty('source_path')) {
                $redirect->source_path = static::normalizePath($redirect->source_path);
                $redirect->source_path_hash = hash('sha256', $redirect->source_path);
            }
        });

        // Invalidate cache on any change
        static::created(fn () => Cache::forget('tallcms.redirects'));
        static::updated(fn () => Cache::forget('tallcms.redirects'));
        static::deleted(fn () => Cache::forget('tallcms.redirects'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Normalize a URL path for consistent storage and matching.
     *
     * - Ensures leading slash
     * - Strips trailing slash (except for root "/")
     * - No lowercasing (TallCMS slugs are case-preserved)
     */
    public static function normalizePath(string $path): string
    {
        $path = '/'.ltrim($path, '/');

        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        return $path;
    }
}
