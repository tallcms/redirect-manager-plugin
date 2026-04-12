<?php

namespace Tallcms\RedirectManager\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Tallcms\RedirectManager\Models\Redirect;

class NoSelfRedirect implements DataAwareRule, ValidationRule
{
    protected array $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $sourcePath = $this->data['source_path'] ?? null;

        if ($sourcePath === null || $value === null) {
            return;
        }

        $normalizedSource = Redirect::normalizePath($sourcePath);
        $normalizedDestination = $this->normalizeDestination($value);

        if ($normalizedDestination !== null && $normalizedSource === $normalizedDestination) {
            $fail('The destination cannot be the same as the source path (this would create an infinite redirect loop).');
        }
    }

    /**
     * Normalize a destination URL to a path for comparison.
     *
     * Returns null for external URLs (different host) since those
     * can't loop with a local source path.
     */
    protected function normalizeDestination(string $destination): ?string
    {
        // Relative path — normalize directly
        if (str_starts_with($destination, '/')) {
            return Redirect::normalizePath($destination);
        }

        // Absolute URL — extract path and check if it's the same host
        $parsed = parse_url($destination);

        if (! isset($parsed['host'])) {
            // Malformed URL without host — treat as relative
            return Redirect::normalizePath('/'.$destination);
        }

        // Compare host against the current app URL
        $appHost = parse_url(config('app.url', ''), PHP_URL_HOST);

        if ($appHost && strtolower($parsed['host']) === strtolower($appHost)) {
            return Redirect::normalizePath($parsed['path'] ?? '/');
        }

        // External URL — can't self-loop
        return null;
    }
}
