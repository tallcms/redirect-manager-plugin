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
        'site_id',
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

        // Invalidate cache on any change — site-scoped when multisite active
        $clearCache = function (self $redirect) {
            Cache::forget('tallcms.redirects'); // Always clear global
            if (isset($redirect->site_id) && $redirect->site_id) {
                Cache::forget("tallcms.redirects.{$redirect->site_id}");
            }
        };

        static::created($clearCache);
        static::updated($clearCache);
        static::deleted($clearCache);
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
