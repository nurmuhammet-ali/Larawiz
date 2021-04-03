<?php

namespace Tests\Model;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class ColumnTimestampsTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_includes_default_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS .'User.php'));

        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringNotContainsString('public $timestamps', $model);

        $migration = $this->filesystem->get($this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('$table->timestamps();', $migration);
    }

    public function test_quick_model_swaps_timestamps_for_timestamps_timezone()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'name' => 'string',
                    'timestampsTz' => null,
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringNotContainsString('public $timestamps', $model);

        $migration = $this->filesystem->get($this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('$table->timestampsTz();', $migration);
    }

    public function test_custom_model_does_not_include_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'name' => 'string'
                    ]
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        $this->assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        $this->assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringContainsString('public $timestamps = false', $model);

        $migration = $this->filesystem->get($this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('$table->timestamps();', $migration);
        $this->assertStringNotContainsString('$table->timestampsTz();', $migration);
    }

    public function test_custom_model_points_custom_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'name' => 'string',
                        'creation_date' => 'timestamp nullable',
                    ],
                    'timestamps' => [
                        'created_at' => 'creation_date',
                    ]
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $creation_date', $model);
        $this->assertStringContainsString("public const CREATED_AT = 'creation_date';", $model);
        $this->assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringContainsString('public const UPDATED_AT = null;', $model);
        $this->assertStringNotContainsString('public $timestamps = false', $model);

        $migration = $this->filesystem->get($this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('$table->timestampsTz();', $migration);
        $this->assertStringNotContainsString('$table->timestamp(null)->nullable();', $migration);
        $this->assertStringContainsString("\$table->timestamp('creation_date')->nullable();", $migration);
    }

    public function test_error_when_timestamp_column_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [doesnt_exists] timestamp column doesn't exists in the [User] model.");

        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'name' => 'string',
                        'creation_date' => 'timestamp nullable',
                    ],
                    'timestamps' => [
                        'created_at' => 'doesnt_exists',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_timestamps_column_timestamp_not_nullable()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "The [creation_date] column of [User] must be [timestamp|timestampTz] and [nullable]."
        );

        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'name' => 'string',
                        'creation_date' => 'timestamp',
                    ],
                    'timestamps' => [
                        'created_at' => 'creation_date',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_timestamps_column_nullable_not_timestamp()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "The [creation_date] column of [User] must be [timestamp|timestampTz] and [nullable]."
        );

        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'name' => 'string',
                        'creation_date' => 'datetime nullable',
                    ],
                    'timestamps' => [
                        'created_at' => 'creation_date',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
