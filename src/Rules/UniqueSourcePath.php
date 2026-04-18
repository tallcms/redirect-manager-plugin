<?php

namespace Tallcms\RedirectManager\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tallcms\RedirectManager\Models\Redirect;

class UniqueSourcePath implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $normalized = Redirect::normalizePath($value);
        $hash = hash('sha256', $normalized);

        $query = DB::table('tallcms_redirects')->where('source_path_hash', $hash);

        // When used from Filament forms without an explicit ignoreId,
        // try to resolve the current record from the route parameter
        $ignoreId = $this->ignoreId ?? request()->route('record');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        // Scope to current site when multisite is active
        if (Schema::hasColumn('tallcms_redirects', 'site_id')) {
            $siteId = $this->resolveCurrentSiteId();
            if ($siteId) {
                $query->where('site_id', $siteId);
            }
        }

        if ($query->exists()) {
            $fail('A redirect for this path already exists.');
        }
    }

    protected function resolveCurrentSiteId(): ?int
    {
        $sessionValue = session('multisite_admin_site_id');
        if ($sessionValue && $sessionValue !== '__all_sites__' && is_numeric($sessionValue)) {
            return (int) $sessionValue;
        }

        if (app()->bound('tallcms.multisite.resolver')) {
            try {
                $resolver = app('tallcms.multisite.resolver');
                if ($resolver->isResolved() && $resolver->id()) {
                    return $resolver->id();
                }
            } catch (\Throwable) {
            }
        }

        return null;
    }
}
