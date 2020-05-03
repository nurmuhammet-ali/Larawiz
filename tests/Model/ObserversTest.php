<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Larawiz\Larawiz\Larawiz;
use Orchestra\Testbench\TestCase;
use Illuminate\Filesystem\Filesystem;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class ObserversTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_custom_model_does_not_create_observer()
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

        $this->assertFileNotExistsInFilesystem($this->app->path('Observers' . DS . 'UserObserver.php'));
    }

    public function test_custom_model_creates_observer()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                    'observer' => true,
                ],
            ],
        ]);

        $this->shouldUseArrayFilesystem(false);

        $fileystem = $this->partialMock(Filesystem::class);

        $fileystem->shouldReceive('exists')
            ->with($this->app->basePath(Larawiz::PATH . DS . 'database.yml'))
            ->andReturnTrue();

        $fileystem->shouldReceive('get')
            ->with($this->app->basePath(Larawiz::PATH . DS . 'database.yml'))
            ->andReturnTrue('');

        $this->artisan('larawiz:scaffold');

        $observer = $this->app->path('Observers' . DS . 'UserObserver.php');

        $this->assertFileExistsInFilesystem($observer);

        $this->assertStringContainsString('namespace App\Observers;', $this->filesystem->get($observer));
        $this->assertStringContainsString('use App\User;', $this->filesystem->get($observer));
        $this->assertStringContainsString('class UserObserver', $this->filesystem->get($observer));
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
