<?php

namespace Tallcms\RedirectManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Tallcms\RedirectManager\Models\Redirect;

class HandleRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only handle GET/HEAD requests — don't redirect form submissions
        if (! in_array($request->method(), ['GET', 'HEAD'])) {
            return $next($request);
        }

        $path = Redirect::normalizePath($request->getPathInfo());

        // Never redirect protected paths — prevents lockout from admin panel
        if ($this->isProtectedPath($path)) {
            return $next($request);
        }

        $redirects = $this->getRedirectMap();

        if (isset($redirects[$path])) {
            $match = $redirects[$path];

            // Safety: skip self-redirects to prevent infinite loops
            $destinationPath = $this->resolveDestinationPath($request, $match['destination_url']);
            if ($destinationPath === $path) {
                return $next($request);
            }

            // Atomic hit count update — best-effort analytics
            try {
                Redirect::where('id', $match['id'])->update([
                    'hit_count' => DB::raw('hit_count + 1'),
                    'last_hit_at' => now(),
                ]);
            } catch (\Throwable) {
                // Don't let hit tracking break the redirect
            }

            return redirect($match['destination_url'], $match['status_code']);
        }

        return $next($request);
    }

    protected function resolveDestinationPath(Request $request, string $destination): ?string
    {
        if (str_starts_with($destination, '/')) {
            return Redirect::normalizePath($destination);
        }

        $parsed = parse_url($destination);

        if (! isset($parsed['host'])) {
            return Redirect::normalizePath('/'.$destination);
        }

        $host = strtolower($parsed['host']);

        // Check against the actual request host (covers multisite, alternate domains)
        if ($host === strtolower($request->getHost())) {
            return Redirect::normalizePath($parsed['path'] ?? '/');
        }

        // Check against configured app URL
        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST);
        if ($appHost && $host === strtolower($appHost)) {
            return Redirect::normalizePath($parsed['path'] ?? '/');
        }

        return null;
    }

    /**
     * Check if a path is protected from redirects.
     *
     * Prevents redirect rules from hijacking the admin panel, API,
     * Livewire, or plugin routes — which would lock admins out.
     */
    protected function isProtectedPath(string $path): bool
    {
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $protectedPrefixes = [
            "/{$panelPath}",
            '/api',
            '/livewire',
            '/_plugins',
        ];

        foreach ($protectedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    protected function getRedirectMap(): array
    {
        return Cache::remember('tallcms.redirects', 3600, function () {
            if (! Schema::hasTable('tallcms_redirects')) {
                return [];
            }

            return Redirect::active()
                ->get(['id', 'source_path', 'destination_url', 'status_code'])
                ->keyBy('source_path')
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'destination_url' => $r->destination_url,
                    'status_code' => $r->status_code,
                ])
                ->all();
        });
    }
}
