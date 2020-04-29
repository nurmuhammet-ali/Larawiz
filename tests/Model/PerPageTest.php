<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;

class PerPageTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_sets_default_per_page()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertStringNotContainsString(
            'protected $perPage = 15;',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_custom_model_sets_default_per_page()
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

        $this->artisan('larawiz:scaffold');

        $this->assertStringNotContainsString(
            'protected $perPage = 15;',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_model_changes_per_page_number()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                    'perPage' => 30
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertStringContainsString(
            'protected $perPage = 30;',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
