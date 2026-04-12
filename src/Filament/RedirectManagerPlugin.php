<?php

namespace Tallcms\RedirectManager\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Tallcms\RedirectManager\Filament\Resources\Redirects\RedirectResource;

class RedirectManagerPlugin implements Plugin
{
    public function getId(): string
    {
        return 'tallcms-redirect-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            RedirectResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }
}
