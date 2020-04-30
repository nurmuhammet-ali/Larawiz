<?php

namespace Tests\Model;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class TableNameTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_table_pluralizes_class_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php');
        $model = $this->app->path('User.php');

        $this->assertFileExistsInFilesystem($migration);
        $this->assertFileExistsInFilesystem($model);

        $this->assertStringContainsString('class CreateUsersTable extends Migration',
            $this->filesystem->get($migration));
        $this->assertStringContainsString("Schema::create('users',",
            $this->filesystem->get($migration));

        $this->assertStringNotContainsString("protected \$table = 'users';",
            $this->filesystem->get($model));
    }

    public function test_quick_model_sets_table_as_column_when_issued()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'table' => 'app_users',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php');
        $model = $this->app->path('User.php');

        $this->assertFileExistsInFilesystem(
            $file = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        $this->assertStringContainsString('class CreateUsersTable extends Migration',
            $this->filesystem->get($migration));
        $this->assertStringContainsString("Schema::create('users',",
            $this->filesystem->get($migration));

        $this->assertStringNotContainsString("protected \$table = 'users';",
            $this->filesystem->get($model));

    }

    public function test_custom_model_table_pluralizes_class_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php');
        $model = $this->app->path('User.php');

        $this->assertFileExistsInFilesystem($migration);
        $this->assertFileExistsInFilesystem($model);

        $this->assertStringContainsString('class CreateUsersTable extends Migration',
            $this->filesystem->get($migration));
        $this->assertStringContainsString("Schema::create('users',",
            $this->filesystem->get($migration));

        $this->assertStringNotContainsString("protected \$table = 'users';",
            $this->filesystem->get($model));
    }

    public function test_model_forces_table_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'table' => 'app_users',
                    'columns' => [
                        'name' => 'string'
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_app_users_table.php');
        $model = $this->app->path('User.php');

        $this->assertFileExistsInFilesystem($migration);
        $this->assertFileExistsInFilesystem($model);

        $this->assertStringContainsString('class CreateAppUsersTable extends Migration',
            $this->filesystem->get($migration));
        $this->assertStringContainsString("Schema::create('app_users',",
            $this->filesystem->get($migration));

        $this->assertStringContainsString("protected \$table = 'app_users';",
            $this->filesystem->get($model));
    }

    public function test_error_when_table_name_is_duplicated_manually_or_automatically()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The table [app_users] is duplicated in [User].');

        $this->mockDatabaseFile([
            'models' => [
                'AppUser' => [
                    'name' => 'string',
                ],
                'User' => [
                    'table' => 'app_users',
                    'columns' => [
                        'name' => 'string'
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
