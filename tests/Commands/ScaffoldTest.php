<?php

namespace Tests\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Larawiz;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Scaffolding\Pipes\ParseDatabaseData;
use LogicException;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Yaml\Yaml;
use Tests\CleansProjectFromScaffoldData;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class ScaffoldTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;

    /** @noinspection DisconnectedForeachInstructionInspection */
    public function test_receives_filenames()
    {
        $files = [
            'larawiz' . DS . 'database.yml',
            'larawiz' . DS . 'database.yaml',
            'larawiz' . DS . 'db.yml',
            'larawiz' . DS . 'db.yaml',
            'larawiz' . DS . 'model.yml',
            'larawiz' . DS . 'model.yaml',
            'larawiz' . DS . 'models.yml',
            'larawiz' . DS . 'models.yaml',
        ];

        foreach ($files as $file) {
            File::makeDirectory($this->app->basePath('larawiz'));
            File::put($this->app->basePath($file), Yaml::dump([
                'models' => [
                    'Foo' => [
                        'bar' => 'string',
                    ],
                ],
            ]));
            $this->artisan('larawiz:scaffold')->run();
            static::assertFileExists($this->app->path('Models/Foo.php'));
            $this->cleanProject();
        }
    }

    public function test_receives_custom_database_filename()
    {
        File::makeDirectory($this->app->basePath('larawiz'));

        File::put($this->app->basePath('custom.yml'), Yaml::dump([
            'models' => [
                'Foo' => [
                    'bar' => 'string',
                ],
            ],
        ]));

        $this->artisan('larawiz:scaffold --db=custom.yml')->run();
        static::assertFileExists($this->app->path('Models/Foo.php'));
    }

    public function test_error_no_custom_database_filename_found()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The scaffold file for [database] was not found');
        $this->artisan('larawiz:scaffold --db=custom.yml');
    }

    public function test_error_if_no_database_files_are_found()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The scaffold file for [database] was not found');

        $this->artisan('larawiz:scaffold');
    }

    public function test_backups_app_migrations_seeds_and_factories_folders()
    {
        File::ensureDirectoryExists($this->app->path('Models'));
        File::ensureDirectoryExists($this->app->path('Casts'));
        File::ensureDirectoryExists($this->app->databasePath('migrations'), null, true);
        File::ensureDirectoryExists($this->app->databasePath('factories'), null, true);
        File::ensureDirectoryExists($this->app->databasePath('seeders'), null, true);

        File::put($this->app->path('Models' . DS . 'Foo.php'), 'test');
        File::put($this->app->databasePath('migrations' . DS . 'Bar.php'), 'test');
        File::put($this->app->databasePath('factories' . DS . 'Quz.php'), 'test');
        File::put($this->app->databasePath('seeders' . DS . 'Qux.php'), 'test');
        File::put($this->app->path('Casts' . DS . 'Quux.php'), 'test');

        $scaffold = Scaffold::make();
        $scaffold->rawDatabase->set('models', [
            'Foo' => [
                'bar' => 'string',
            ],
        ]);

        $this->mock(ParseDatabaseData::class)
            ->shouldReceive('handle')
            ->once()
            ->andReturn($scaffold);

        Carbon::setTestNow($time = Carbon::parse('2020-04-01 19:00:00'));

        $this->artisan('larawiz:scaffold');

        $path = Larawiz::BACKUPS_DIR . DS . $time->format('Y-m-d_His');

        static::assertDirectoryExists(storage_path($path));

        $appDir = Str::of(rtrim($this->app->path(), '\\'))->afterLast(DS)->__toString();
        $databaseDir = Str::of(rtrim($this->app->databasePath(), '\\'))->afterLast(DS)->__toString();

        static::assertFileExists(storage_path($path . DS . $appDir . DS . 'Models' . DS . 'Foo.php'));
        static::assertFileExists(storage_path($path . DS . $databaseDir . DS . 'migrations' . DS . 'Bar.php'));
        static::assertFileExists(storage_path($path . DS . $databaseDir . DS . 'factories' . DS . 'Quz.php'));
        static::assertFileExists(storage_path($path . DS . $databaseDir . DS . 'seeders' . DS . 'Qux.php'));
        static::assertFileExists(storage_path($path . DS . $appDir . DS . 'Casts' . DS . 'Quux.php'));
    }

    public function test_accepts_no_backups_flag_and_doesnt_backups()
    {
        $this->artisan('larawiz:sample')->run();

        File::ensureDirectoryExists($this->app->path('Models'));
        File::makeDirectory($this->app->databasePath('migrations'), null, null, true);
        File::makeDirectory($this->app->databasePath('factories'), null, null, true);
        File::makeDirectory($this->app->databasePath('seeders'), null, null, true);

        File::put($this->app->path('Models' . DS . 'Foo.php'), 'test');
        File::put($this->app->databasePath('migrations' . DS . 'Bar.php'), 'test');
        File::put($this->app->databasePath('factories' . DS . 'Quz.php'), 'test');
        File::put($this->app->databasePath('seeders' . DS . 'Qux.php'), 'test');
        File::put($this->app->path('Casts' . DS . 'Quux.php'), 'test');

        $this->artisan('larawiz:scaffold --no-backup');

        Carbon::setTestNow($time = Carbon::parse('2020-04-01 19:00:00'));

        $path = 'larawiz' . DS . 'backups' . DS . $time->format('Y-m-d_His');

        static::assertDirectoryDoesNotExist(storage_path($path));

        static::assertFileExists($this->app->path('Models' . DS . 'Foo.php'));
        static::assertFileExists($this->app->databasePath('migrations' . DS . 'Bar.php'));
        static::assertFileExists($this->app->databasePath('factories' . DS . 'Quz.php'));
        static::assertFileExists($this->app->databasePath('seeders' . DS . 'Qux.php'));
        static::assertFileExists($this->app->path('Casts' . DS . 'Quux.php'));
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
