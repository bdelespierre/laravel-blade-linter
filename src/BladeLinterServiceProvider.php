<?php

namespace Bdelespierre\LaravelBladeLinter;

use Bdelespierre\LaravelBladeLinter\BladeLinterCommand;
use Illuminate\Support\ServiceProvider;

class BladeLinterServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([BladeLinterCommand::class]);
        }
    }
}
