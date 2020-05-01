<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class ColumnsTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_custom_model_no_error_if_no_columns_are_set()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => []
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('factories' . DS . 'UserFactory.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    public function test_custom_model_creates_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'id' => null
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('$table->id();', $migration);
    }

    public function test_creates_id_with_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'id' => 'foo'
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString('protected $primaryKey = \'foo\';', $model);
        $this->assertStringContainsString('$table->id(\'foo\');', $migration);
    }

    public function test_chains_method_preceded_with_null()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'id' => '~ foo bar:qux,quz',
                        'softDeletes' => '~ foo bar:qux,quz',
                        'foo' => 'bar:~,quz,qux',
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('protected $primaryKey', $model);
        $this->assertStringNotContainsString('protected $keyType', $model);
        $this->assertStringNotContainsString('protected $incrementing', $model);

        $this->assertStringContainsString("\$table->id()->foo()->bar('qux', 'quz');", $migration);
        $this->assertStringContainsString("\$table->softDeletes()->foo()->bar('qux', 'quz');", $migration);
        $this->assertStringContainsString("\$table->bar('foo', null, 'quz', 'qux');", $migration);
    }

    public function test_creates_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'uuid' => null
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString('protected $primaryKey = null;', $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
        $this->assertStringNotContainsString('protected $keyType;', $model);
        $this->assertStringContainsString('$table->uuid();', $migration);
        $this->assertStringContainsString("'uuid' => \$faker->uuid,", $factory);
    }

    public function test_creates_uuid_with_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'uuid' => 'foo'
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString('protected $primaryKey = null;', $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
        $this->assertStringNotContainsString('protected $keyType;', $model);
        $this->assertStringContainsString("\$table->uuid('foo');", $migration);
        $this->assertStringContainsString("'foo' => \$faker->uuid,", $factory);
    }

    public function test_passes_through_column_declaration()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'bar:quz,qux'
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString('protected $primaryKey = null;', $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
        $this->assertStringNotContainsString('protected $keyType;', $model);
        $this->assertStringContainsString("\$table->bar('foo', 'quz', 'qux');", $migration);
        $this->assertStringContainsString("'foo' => \$faker->foo,", $factory);
    }

    public function test_does_not_creates_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'bar:quz,qux'
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        $this->assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringContainsString('public $timestamps = false;', $model);

        $this->assertStringNotContainsString('$table->timestamps();', $migration);
        $this->assertStringNotContainsString('$table->timestampsTz();', $migration);
    }

    public function test_creates_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'timestamps' => null
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringNotContainsString('public $timestamps = false;', $model);

        $this->assertStringContainsString('$table->timestamps();', $migration);
        $this->assertStringNotContainsString('$table->timestampsTz();', $migration);
    }

    public function test_swaps_timestamps_with_timestamps_timezone()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'timestampsTz' => null
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        $this->assertStringContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        $this->assertStringNotContainsString('public $timestamps', $model);

        $this->assertStringNotContainsString('$table->timestamps();', $migration);
        $this->assertStringContainsString('$table->timestampsTz();', $migration);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
