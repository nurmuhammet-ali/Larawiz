<?php

namespace Tests\Model;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class ColumnSoftDeleteTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_does_not_have_soft_delete()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'bar'
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

        $this->assertStringNotContainsString('@property-read null|\Illuminate\Support\Carbon $deleted_at', $model);
        $this->assertStringNotContainsString('use SoftDeletes;', $model);

        $this->assertStringNotContainsString('$table->softDeletes', $migration);
    }

    public function test_creates_soft_delete()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'bar',
                        'softDeletes' => null
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

        $this->assertStringContainsString('@property-read null|\Illuminate\Support\Carbon $deleted_at', $model);
        $this->assertStringContainsString('use SoftDeletes;', $model);

        $this->assertStringContainsString('$table->softDeletes();', $migration);
        $this->assertStringNotContainsString('$table->softDeletes(\'deleted_at\');', $migration);
    }

    public function test_creates_soft_delete_with_custom_column_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'bar',
                        'softDeletes' => 'quz'
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

        $this->assertStringContainsString('@property-read null|\Illuminate\Support\Carbon $quz', $model);
        $this->assertStringContainsString('use SoftDeletes;', $model);
        $this->assertStringContainsString("public const DELETED_AT = 'quz';", $model);

        $this->assertStringContainsString("\$table->softDeletes('quz');", $migration);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
