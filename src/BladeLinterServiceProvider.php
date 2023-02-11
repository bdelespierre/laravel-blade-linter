<?php
declare(strict_types=1);

namespace Bdelespierre\LaravelBladeLinter;

use Illuminate\Support\ServiceProvider;

final class BladeLinterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([BladeLinterCommand::class]);
        }
    }
}
