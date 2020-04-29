<?php

namespace Tests\Model;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class ColumnIndexesTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_creates_column_with_index()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'foo' => 'bar index'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString("\$table->bar('foo')->index();", $migration);
    }

    public function test_creates_column_with_unique()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'foo' => 'bar unique'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString("\$table->bar('foo')->unique();", $migration);
    }

    public function test_error_when_mixing_index_with_unique()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [foo] column must contain either [index] or [unique], not both.');

        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'foo' => 'bar unique index'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_indexes_from_list()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'string',
                        'bar' => 'string',
                        'quz' => 'string',
                        'qux' => 'string',
                        'quuz' => 'string',
                        'quux' => 'string',
                    ],
                    'indexes' => [
                        'foo',
                        'bar quz',
                        'qux quuz name:custom_index_name',
                        'quuz quux unique'
                    ]
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        $this->assertStringContainsString("\$table->index(['foo']);", $migration);
        $this->assertStringContainsString("\$table->index(['bar', 'quz']);", $migration);
        $this->assertStringContainsString("\$table->index(['qux', 'quuz'], 'custom_index_name');", $migration);
        $this->assertStringContainsString("\$table->unique(['quuz', 'quux']);", $migration);
    }

    public function test_error_when_index_list_contains_non_existent_columns()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [quz] doesn't exists in the [User] to make an index.");

        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'string',
                    ],
                    'indexes' => [
                        'foo quz',
                    ]
                ]
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
