<?php

namespace Larawiz\Larawiz;

use Illuminate\Support\ServiceProvider;

class LarawizServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\SampleCommand::class,
                Console\ScaffoldCommand::class,
                Console\ClearBackupsCommand::class,
            ]);
        }
    }
}
