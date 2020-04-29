<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class SeedersTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_automatically_creates_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExists($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    public function test_custom_model_automatically_creates_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExists($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    public function test_disables_seeder()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'seeder' => false,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExists($this->app->databasePath('seeds' . DS . 'UserSeeder.php'));
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
