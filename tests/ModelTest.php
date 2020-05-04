<?php

namespace Tests;

use LogicException;
use Orchestra\Testbench\TestCase;
use const DIRECTORY_SEPARATOR as DS;

class ModelTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_custom_model_error_if_list()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Ensure the [User] is a model declaration.');

        $this->mockDatabaseFile([
            'models' => [
                'User',
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_custom_model_error_model_doesnt_contains_list()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Ensure the [User] is a model declaration.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => ''
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_custom_model_error_if_declaration_is_list()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Ensure the [User] is a model declaration.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo', 'bar'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_quick_model_error_if_no_columns_are_declared()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Ensure the [User] is a model declaration.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => []
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_model_in_default_app_namespace()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Foo'     => [
                    'name' => 'name',
                ],
                'Qux\Quz' => [
                    'name' => 'name',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('Foo.php'));
        $this->assertStringContainsString(
            'namespace App;',
            $this->filesystem->get($this->app->path('Foo.php'))
        );

        $this->assertFileExistsInFilesystem($this->app->path('Qux' . DS . 'Quz.php'));
        $this->assertStringContainsString(
            'namespace App\Qux;',
            $this->filesystem->get($this->app->path('Qux' . DS . 'Quz.php'))
        );
    }

    public function test_creates_model_in_custom_namespace()
    {
        $this->mockDatabaseFile([
            'namespace' => 'Foo',
            'models'    => [
                'Foo'     => [
                    'name' => 'name',
                ],
                'Qux\Quz' => [
                    'name' => 'name',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('Foo' . DS . 'Foo.php'));
        $this->assertStringContainsString(
            'namespace App\Foo;',
            $this->filesystem->get($this->app->path('Foo' . DS . 'Foo.php'))
        );

        $this->assertFileExistsInFilesystem($this->app->path('Foo' . DS . 'Qux' . DS . 'Quz.php'));
        $this->assertStringContainsString(
            'namespace App\Foo\Qux;',
            $this->filesystem->get($this->app->path('Foo' . DS . 'Qux' . DS . 'Quz.php'))
        );
    }

    public function test_error_when_model_classes_duplicated()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The model class name [Quz] is duplicated in [Qux\Quz, Foo\Quz].');

        $this->mockDatabaseFile([
            'models' => [
                'Quz'     => [
                    'name' => 'name',
                ],
                'Qux\Quz' => [
                    'name' => 'name',
                ],
                'Foo\Quz' => [
                    'name' => 'name',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_no_models_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No models where detected. Are you sure you filled the [models] key?');

        $this->mockDatabaseFile([
            'model' => [
                'User' => [
                    'foo' => 'bar'
                ]
            ]
        ]);

        $this->artisan('larawiz:scaffold');
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
