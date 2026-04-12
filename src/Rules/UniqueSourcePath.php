<?php

namespace Tallcms\RedirectManager\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
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

        $query = Redirect::where('source_path_hash', $hash);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('A redirect for this path already exists.');
        }
    }
}
