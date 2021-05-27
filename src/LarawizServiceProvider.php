<?php

namespace Larawiz\Larawiz;

use Illuminate\Support\ServiceProvider;
use Larawiz\Larawiz\Console\ScaffoldCommand;

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
            $this->app->singleton(ScaffoldCommand::class);

            $this->commands([
                Console\SampleCommand::class,
                Console\ScaffoldCommand::class,
                Console\ClearBackupsCommand::class,
            ]);
        }
    }
}
