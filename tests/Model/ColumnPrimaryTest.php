<?php

namespace Tests\Model;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class ColumnPrimaryTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_includes_default_primary_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'bar',
                ],
                'Post' => [
                    'user' => 'belongsTo'
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
        static::assertStringContainsString('@property int $id', $model);
        static::assertStringContainsString('$table->id();', $migration);
    }

    public function test_quick_model_changes_primary_id_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'id' => 'foo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property int $foo', $model);
        static::assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        static::assertStringContainsString("\$table->id('foo');", $migration);
    }

    public function test_quick_model_changes_primary_to_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'uuid' => null,
                    'foo'  => 'bar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property string $uuid', $model);
        static::assertStringContainsString("protected \$primaryKey = 'uuid';", $model);

        static::assertStringContainsString("\$table->uuid('uuid')->primary();", $migration);
        static::assertStringNotContainsString("\$table->primary('uuid');", $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
    }

    public function test_quick_model_changes_primary_to_uuid_with_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'uuid' => 'quz',
                    'foo'  => 'bar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property string $quz', $model);
        static::assertStringContainsString("protected \$primaryKey = 'quz';", $model);
        static::assertStringContainsString("\$table->uuid('quz')->primary();", $migration);
        static::assertStringNotContainsString("\$table->primary('quz');", $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
    }

    public function test_accepts_uuid_named_as_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'uuid' => 'id',
                    'foo'  => 'bar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property string $id', $model);
        static::assertStringNotContainsString("protected \$primaryKey = 'id';", $model);
        static::assertStringContainsString("\$table->uuid('id')->primary();", $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
        static::assertStringNotContainsString("\$table->primary('id');", $migration);
    }

    public function test_error_when_quick_model_has_more_than_one_incrementing_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [User] has more than one auto-incrementing column.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo'   => 'id',
                    'bar'   => 'increments',
                    'quz'   => 'integerIncrements',
                    'qux'   => 'tinyIncrements',
                    'quux'  => 'smallIncrements',
                    'quuz'  => 'mediumIncrements',
                    'corge' => 'bigIncrements',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_model_does_not_includes_primary()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('@property int $id', $model);
        static::assertStringContainsString('protected $primaryKey = null;', $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringNotContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->primary(', $migration);
    }

    public function test_model_receives_primary_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'id' => null,
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property int $id', $model);
        static::assertStringContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->primary(\'id\');', $migration);
    }

    public function test_model_receives_primary_id_with_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'id' => 'foo',
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property int $foo', $model);
        static::assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        static::assertStringContainsString("\$table->id('foo');", $migration);
        static::assertStringNotContainsString('$table->primary(\'id\');', $migration);
    }

    public function test_model_doesnt_receives_uuid_as_primary()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'id' => null,
                        'uuid' => null,
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property int $id', $model);
        static::assertStringContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->uuid()->primary();', $migration);
        static::assertStringNotContainsString('$table->primary(\'uuid\');', $migration);
    }

    public function test_model_primary_set_to_string_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'primary' => 'name'
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('@property int $id', $model);
        static::assertStringContainsString("protected \$primaryKey = 'name';", $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringContainsString("protected \$keyType = 'string';", $model);
        static::assertStringContainsString('$table->string(\'name\')->primary();', $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->primary(', $migration);
    }

    public function test_model_primary_set_to_integer_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'integer',
                    ],
                    'primary' => 'name'
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('@property int $id', $model);
        static::assertStringContainsString("protected \$primaryKey = 'name';", $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringNotContainsString("protected \$keyType = 'int';", $model);

        static::assertStringContainsString('$table->integer(\'name\')->primary();', $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->primary(\'name\');', $migration);
    }

    public function test_model_primary_set_to_timestamp_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'timestamp',
                    ],
                    'primary' => 'name'
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('@property int $id', $model);
        static::assertStringContainsString("protected \$primaryKey = 'name';", $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringContainsString("protected \$keyType = 'datetime';", $model);
        static::assertStringContainsString('$table->timestamp(\'name\')->primary();', $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->primary(\'name\');', $migration);
    }

    public function test_model_primary_set_to_custom_properties_casts_to_string()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'bar',
                    ],
                    'primary' => 'foo'
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString('@property int $id', $model);
        static::assertStringContainsString('@property string $foo', $model);
        static::assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringContainsString("protected \$keyType = 'string';", $model);
        static::assertStringContainsString('$table->bar(\'foo\')->primary();', $migration);
        static::assertStringNotContainsString('$table->id();', $migration);
        static::assertStringNotContainsString('$table->primary(\'foo\');', $migration);
    }

    public function test_can_have_incrementing_key_and_set_different_primary()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'id' => null,
                        'foo' => 'bar'
                    ],
                    'primary' => 'foo'
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString('@property int $id', $model);
        static::assertStringContainsString('@property string $foo', $model);
        static::assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        static::assertStringContainsString('public $incrementing = false;', $model);
        static::assertStringContainsString("protected \$keyType = 'string';", $model);
        static::assertStringContainsString('$table->id();', $migration);
    }

    public function test_error_if_primary_column_set_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [bar] primary column in [User] doesn't exists.");

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'bar'
                    ],
                    'primary' => 'bar'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_adds_uuid_trait_to_model_using_uuid_has_primary_key()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Thing\User' => [
                    'uuid' => null,
                    'foo'  => 'bar',
                ],
                'Bar' => [
                    'uuid' => null,
                    'foo'  => 'bar',
                ],
            ],
        ]);

        $this->shouldMockUuidTraitFile(false);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('Models' . DS . 'HasUuidPrimaryKey.php'));

        static::assertStringContainsString('namespace App\Models;',
                                          $this->filesystem->get($this->app->path('Models' . DS . 'HasUuidPrimaryKey.php'))
        );

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Thing' . DS .'User.php'));

        static::assertStringContainsString("use App\Models\HasUuidPrimaryKey;", $model);
        static::assertStringContainsString("    use HasUuidPrimaryKey;", $model);

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Bar.php'));

        static::assertStringNotContainsString("use App\Models\HasUuidPrimaryKey;", $model);
        static::assertStringContainsString("    use HasUuidPrimaryKey;", $model);
    }

    public function test_no_free_traits_doesnt_adds_uuid_free_trait()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Thing\User' => [
                    'uuid' => null,
                    'foo'  => 'bar',
                ],
                'Bar' => [
                    'uuid' => null,
                    'foo'  => 'bar',
                ],
            ],
        ]);

        $this->shouldMockUuidTraitFile(true);

        $this->artisan('larawiz:scaffold', [
            '--no-free-traits' => true,
        ]);

        $this->assertFileNotExistsInFilesystem($this->app->path('Models' . DS . 'HasUuidPrimaryKey.php'));

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Thing' . DS .'User.php'));

        static::assertStringNotContainsString("use App\Models\HasUuidPrimaryKey;", $model);
        static::assertStringNotContainsString("    use HasUuidPrimaryKey;", $model);

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Bar.php'));

        static::assertStringNotContainsString("    use HasUuidPrimaryKey;", $model);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
