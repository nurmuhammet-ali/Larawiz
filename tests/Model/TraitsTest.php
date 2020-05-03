<?php

namespace Tests\Model;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class TraitsTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_creates_traits_from_list()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Foo',
                        'Bar\Quz'
                    ]
                ],
                'Post' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar',
                        'Quz\Qux'
                    ]
                ]
            ],
        ]);

        $this->shouldMockTraitFile(false);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('Foo.php'));
        $this->assertStringContainsString('trait Foo',
            $this->filesystem->get($this->app->path('Foo.php')));
        $this->assertStringContainsString('initializeFoo',
            $this->filesystem->get($this->app->path('Foo.php')));
        $this->assertStringContainsString('bootFoo',
            $this->filesystem->get($this->app->path('Foo.php')));

        $this->assertFileExistsInFilesystem($this->app->path('Bar' . DS . 'Quz.php'));
        $this->assertStringContainsString('trait Quz',
            $this->filesystem->get($this->app->path('Bar' . DS . 'Quz.php')));
        $this->assertStringContainsString('initializeQuz',
            $this->filesystem->get($this->app->path('Bar' . DS . 'Quz.php')));
        $this->assertStringContainsString('bootQuz',
            $this->filesystem->get($this->app->path('Bar' . DS . 'Quz.php')));
    }

    public function test_traits_can_be_referenced_multiple_times()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar\Quz'
                    ]
                ],
                'Post' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar\Quz'
                    ]
                ],
                'Comment' => [
                    'name' => 'string',
                    'traits' => [
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->shouldMockTraitFile(false);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('Bar' . DS . 'Quz.php'));
        $this->assertStringContainsString('trait Quz',
            $this->filesystem->get($this->app->path('Bar' . DS . 'Quz.php')));
        $this->assertStringContainsString('initializeQuz',
            $this->filesystem->get($this->app->path('Bar' . DS . 'Quz.php')));
        $this->assertStringContainsString('bootQuz',
            $this->filesystem->get($this->app->path('Bar' . DS . 'Quz.php')));
    }

    public function test_error_when_traits_collides_with_models_paths()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The following traits collide with the models: User.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'User',
                        'Bar\Quz'
                    ]
                ],
                'Post' => [
                    'name' => 'string',
                    'traits' => [
                        'User',
                        'Quz\Qux'
                    ]
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_trait_not_set_when_using_string()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => 'Foo'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Foo.php'));
    }

    public function test_external_trait_is_only_appended()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Illuminate\Foundation\Validation\ValidatesRequests',
                        'Foo',
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem(
            $this->app->path('Illuminate' . DS . 'Foundation' . DS . 'Validation' . DS . 'ValidatesRequests.php')
        );

        $this->assertFileExistsInFilesystem($this->app->path('Foo.php'));
        $this->assertFileExistsInFilesystem($this->app->path('Bar' . DS . 'Quz.php'));
    }

    public function test_error_when_external_trait_is_not_trait_but_class()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Illuminate\Foundation\Mix] exists but is not a trait, but a class or interface.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Illuminate\Foundation\Mix',
                        'Foo',
                        'Bar\Quz'
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_external_trait_is_not_trait_but_interface()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [Illuminate\Contracts\Auth\Guard] exists but is not a trait, but a class or interface.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'traits' => [
                        'Illuminate\Contracts\Auth\Guard',
                        'Foo',
                        'Bar\Quz'
                    ]
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
