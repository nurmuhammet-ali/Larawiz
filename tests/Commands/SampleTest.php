<?php

namespace Tests\Commands;

use Illuminate\Support\Facades\File;
use Larawiz\Larawiz\Larawiz;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class SampleTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;

    public function test_publishes_sample_files()
    {
        $this->artisan('larawiz:sample')->run();
        $this->assertFileExists($this->app->basePath('larawiz' . DS . 'database.yml'));
    }

    public function test_asks_overwriting_files_and_doesnt_overwrite()
    {
        File::makeDirectory($this->app->basePath('larawiz'), null, null, true);
        File::put($this->app->basePath('larawiz/database.yml'), 'foo');
        $this->artisan('larawiz:sample')
            ->expectsConfirmation('Scaffold files already exists! Do you want to overwrite them?');
        $this->assertStringEqualsFile($this->app->basePath('larawiz/database.yml'), 'foo');
    }

    public function test_asks_overwriting_files_and_overwrites()
    {
        File::makeDirectory($this->app->basePath('larawiz'), null, null, true);
        File::put($this->app->basePath('larawiz/database.yml'), 'foo');
        $this->artisan('larawiz:sample')
            ->expectsConfirmation('Scaffold files already exists! Do you want to overwrite them?', 'yes');
        $this->assertFileEquals(
            $this->app->basePath('larawiz' . DS . 'database.yml'),
            Larawiz::samplePath() . DS . 'database.yml'
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
