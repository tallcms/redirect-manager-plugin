<?php

namespace Tallcms\RedirectManager\Providers;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Tallcms\RedirectManager\Http\Middleware\HandleRedirects;

class RedirectManagerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        $this->app[Kernel::class]->prependMiddleware(HandleRedirects::class);
    }
}
