<?php

namespace Tallcms\RedirectManager\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Tallcms\RedirectManager\Models\Redirect;

class NoProtectedPath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $path = Redirect::normalizePath($value);
        $panelPath = config('tallcms.filament.panel_path', 'admin');

        $protectedPrefixes = [
            "/{$panelPath}" => 'admin panel',
            '/api' => 'API',
            '/livewire' => 'Livewire',
            '/_plugins' => 'plugins',
        ];

        foreach ($protectedPrefixes as $prefix => $label) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                $fail("Cannot redirect from {$label} paths — this would break system functionality.");

                return;
            }
        }
    }
}
