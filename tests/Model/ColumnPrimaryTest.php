<?php

namespace Tests\Model;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class ColumnPrimaryTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_includes_primary_id()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'bar',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get(
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

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
            $this->app->path('User.php'));
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('@property string $uuid', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'uuid';", $model);
        $this->assertStringContainsString('$table->uuid();', $migration);
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString('@property string $quz', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'quz';", $model);
        $this->assertStringContainsString("\$table->uuid('quz');", $migration);
        $this->assertStringNotContainsString('$table->id();', $migration);
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString('protected $primaryKey = null;', $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
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
            $this->app->path('User.php'));
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
            $this->app->path('User.php'));
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
            $this->app->path('User.php'));
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'name';", $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'name';", $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'name';", $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
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
            $this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringNotContainsString('@property int $id', $model);
        $this->assertStringContainsString('@property string $foo', $model);
        $this->assertStringContainsString("protected \$primaryKey = 'foo';", $model);
        $this->assertStringContainsString('protected $incrementing = false;', $model);
        $this->assertStringContainsString("protected \$keyType = 'string';", $model);
        $this->assertStringNotContainsString('$table->id();', $migration);
    }

    public function test_error_if_id_is_set_and_primary_is_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [User] already uses the primary column [id].');

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

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_if_primary_column_set_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [bar] column for primary key doesn't exists in [User] model.");

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

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
