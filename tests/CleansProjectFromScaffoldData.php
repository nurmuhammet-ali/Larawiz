<?php

namespace Tests;

use Illuminate\Support\Facades\File;
use const DIRECTORY_SEPARATOR as DS;

trait CleansProjectFromScaffoldData
{
    protected function cleanProject()
    {
        if (File::exists($this->app->basePath('larawiz'))) {
            File::deleteDirectory($this->app->basePath('larawiz'));
        }

        if (File::exists($this->app->basePath('custom.yml'))) {
            File::delete($this->app->basePath('custom.yml'));
        }

        File::deleteDirectory($this->app->path(), true);
        File::deleteDirectory($this->app->storagePath() . DS . 'larawiz');
        File::deleteDirectory($this->app->databasePath() . DS . 'migrations', true);
        File::deleteDirectory($this->app->databasePath() . DS . 'factories', true);
        File::deleteDirectory($this->app->databasePath() . DS . 'seeders', true);
    }
}
