<?php

namespace Bdelespierre\LaravelBladeLinter;

use Illuminate\Support\ServiceProvider;

class BladeLinterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([BladeLinterCommand::class]);
        }
    }
}
