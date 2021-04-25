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

        $this->assertStringNotContainsString('protected $primaryKey', $model);
        $this->assertStringContainsString('@property int $id', $model);
        $this->assertStringContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('@property int $foo', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        $this->assertStringContainsString("\$table->id('foo');", $migration);
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

        $this->assertStringContainsString('@property string $uuid', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'uuid';", $model);

        $this->assertStringContainsString("\$table->uuid('uuid');", $migration);
        $this->assertStringContainsString("\$table->primary('uuid');", $migration);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('@property string $quz', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'quz';", $model);
        $this->assertStringContainsString("\$table->uuid('quz');", $migration);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('@property string $id', $model);
        $this->assertStringNotContainsString("protected \$primaryKey = 'id';", $model);
        $this->assertStringContainsString("\$table->uuid('id');", $migration);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString('protected $primaryKey = null;', $model);
        $this->assertStringContainsString('public $incrementing = false;', $model);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('@property int $id', $model);
        $this->assertStringContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('@property int $foo', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        $this->assertStringContainsString("\$table->id('foo');", $migration);
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

        $this->assertStringContainsString('@property int $id', $model);
        $this->assertStringContainsString('$table->id();', $migration);
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

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'name';", $model);
        $this->assertStringContainsString('public $incrementing = false;', $model);
        $this->assertStringContainsString("protected \$keyType = 'string';", $model);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'name';", $model);
        $this->assertStringContainsString('public $incrementing = false;', $model);
        $this->assertStringNotContainsString("protected \$keyType = 'int';", $model);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'name';", $model);
        $this->assertStringContainsString('public $incrementing = false;', $model);
        $this->assertStringContainsString("protected \$keyType = 'datetime';", $model);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString('@property string $foo', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        $this->assertStringContainsString('public $incrementing = false;', $model);
        $this->assertStringContainsString("protected \$keyType = 'string';", $model);
        $this->assertStringNotContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('@property int $id', $model);
        $this->assertStringContainsString('@property string $foo', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        $this->assertStringContainsString('public $incrementing = false;', $model);
        $this->assertStringContainsString("protected \$keyType = 'string';", $model);
        $this->assertStringContainsString('$table->id();', $migration);
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

        $this->assertStringContainsString('namespace App\Models;',
                                          $this->filesystem->get($this->app->path('Models' . DS . 'HasUuidPrimaryKey.php'))
        );

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Thing' . DS .'User.php'));

        $this->assertStringContainsString("use App\Models\HasUuidPrimaryKey;", $model);
        $this->assertStringContainsString("    use HasUuidPrimaryKey;", $model);

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Bar.php'));

        $this->assertStringNotContainsString("use App\Models\HasUuidPrimaryKey;", $model);
        $this->assertStringContainsString("    use HasUuidPrimaryKey;", $model);
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

        $this->assertStringNotContainsString("use App\Models\HasUuidPrimaryKey;", $model);
        $this->assertStringNotContainsString("    use HasUuidPrimaryKey;", $model);

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Bar.php'));

        $this->assertStringNotContainsString("    use HasUuidPrimaryKey;", $model);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
