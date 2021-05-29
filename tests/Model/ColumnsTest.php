<?php

namespace Tests\Model;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

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

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'User.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('factories' . DS . 'UserFactory.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('seeders' . DS . 'UserSeeder.php'));
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

        static::assertStringContainsString('$table->id();', $migration);
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
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        static::assertStringContainsString('protected $primaryKey = \'foo\';', $model);
        static::assertStringContainsString('$table->id(\'foo\');', $migration);
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
                        'something' => 'string:32',
                        'something_with_args' => 'string:32 string:32 string',
                        'two_args' => 'something:32,2,~,null,foo'
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('protected $primaryKey', $model);
        static::assertStringNotContainsString('protected $keyType', $model);
        static::assertStringNotContainsString('public $incrementing', $model);

        static::assertStringContainsString(<<<'CONTENT'
            $table->id()->foo()->bar('qux', 'quz');
            $table->softDeletes()->foo()->bar('qux', 'quz');
            $table->bar('foo', null, 'quz', 'qux');
            $table->string('something', 32);
            $table->string('something_with_args', 32)->string(32)->string();
            $table->something('two_args', 32, 2, null, null, 'foo');
CONTENT
            ,$migration);
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
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        static::assertStringContainsString('protected $primaryKey = null;', $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringNotContainsString('protected $keyType;', $model);
        static::assertStringContainsString("\$table->uuid('uuid');", $migration);
        static::assertStringContainsString("'uuid' => \$this->faker->uuid,", $factory);
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
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        static::assertStringContainsString('protected $primaryKey = null;', $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringNotContainsString('protected $keyType;', $model);
        static::assertStringContainsString("\$table->uuid('foo');", $migration);
        static::assertStringContainsString("'foo' => \$this->faker->uuid,", $factory);
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
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));
        $factory = $this->filesystem->get(
            $this->app->databasePath('factories' . DS . 'UserFactory.php'));

        static::assertStringContainsString('protected $primaryKey = null;', $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringNotContainsString('protected $keyType;', $model);
        static::assertStringContainsString("\$table->bar('foo', 'quz', 'qux');", $migration);
        static::assertStringContainsString("'foo' => '', // TODO: Add a random generated value for the [foo (bar)] property,", $factory);
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
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        static::assertStringNotContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        static::assertStringContainsString('public $timestamps = false;', $model);

        static::assertStringNotContainsString('$table->timestamps();', $migration);
        static::assertStringNotContainsString('$table->timestampsTz();', $migration);
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
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        static::assertStringContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        static::assertStringNotContainsString('public $timestamps = false;', $model);

        static::assertStringContainsString('$table->timestamps();', $migration);
        static::assertStringNotContainsString('$table->timestampsTz();', $migration);
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

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property-read \Illuminate\Support\Carbon $created_at', $model);
        static::assertStringContainsString('@property-read \Illuminate\Support\Carbon $updated_at', $model);
        static::assertStringNotContainsString('public $timestamps', $model);

        static::assertStringNotContainsString('$table->timestamps();', $migration);
        static::assertStringContainsString('$table->timestampsTz();', $migration);
    }

    public function test_comments_nullable_property_with_null()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'name' => 'string nullable'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString('@property null|string $name', $model);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
