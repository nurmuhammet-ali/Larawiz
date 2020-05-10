<?php

namespace Tests;

use Orchestra\Testbench\TestCase;

class ModelCast extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_model_doesnt_casts_primary_key()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString("'id' => 'integer'", $model);
    }

    public function test_model_doesnt_casts_touch_timestamps()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString("'created_at' => 'datetime'", $model);
        $this->assertStringNotContainsString("'updated_at' => 'datetime'", $model);
    }

    public function test_model_doesnt_casts_soft_deletes()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                    'softDeletes' => null,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString("'softDeletes' => 'datetime'", $model);
    }

    public function test_model_doesnt_casts_belongs_to_column()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                    'bar' => 'belongsTo',
                ],
                'Bar'  => [
                    'name' => 'string',
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString("'bar_id' => 'integer'", $model);
    }

    public function test_model_doesnt_casts_morph_to_column()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                    'bar' => 'morphTo',
                ],
                'Bar'  => [
                    'name' => 'string',
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString('protected $casts = [', $model);
    }

    public function test_model_doesnt_casts_strings()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString('protected $casts = [', $model);
    }

    public function test_model_doesnt_casts_dates()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                    'foo' => 'date',
                    'bar' => 'dateTime',
                    'quz' => 'dateTimeTz',
                    'qux' => 'time',
                    'quuz' => 'timeTz',
                    'quux' => 'timestamp',
                    'corge' => 'timestampTz',
                    'grault' => 'year',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringNotContainsString('protected $casts = [', $model);
    }

    public function test_adds_dates_to_dates_array_except_touch_timestamps_and_soft_deletes()
    {
        $this->mockDatabaseFile([
            'models'    => [
                'Foo'     => [
                    'name' => 'string',
                    'foo' => 'date',
                    'bar' => 'dateTime',
                    'quz' => 'dateTimeTz',
                    'qux' => 'time',
                    'quuz' => 'timeTz',
                    'quux' => 'timestamp',
                    'corge' => 'timestampTz',
                    'grault' => 'year',
                    'softDeletes' => null,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Foo.php'));

        $this->assertStringContainsString("public \$dates = ['foo', 'bar', 'quz', 'qux', 'quuz', 'quux', 'corge', 'grault'];", $model);
    }
}
