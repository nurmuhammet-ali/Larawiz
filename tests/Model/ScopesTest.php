<?php

namespace Tests\Model;

use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class ScopesTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_does_not_creates_local_scopes()
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

        static::assertStringNotContainsString(
            'function scope',
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );
    }

    public function test_creates_local_scopes()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                    'scopes' => [
                        'foo',
                        'bar',
                        'Bar',
                        'Cougar',
                        'foo',
                        'fooCougar',
                        'scopeFoo',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 *
 * @method \Illuminate\Database\Eloquent\Builder foo()
 * @method \Illuminate\Database\Eloquent\Builder bar()
 * @method \Illuminate\Database\Eloquent\Builder fooCougar()
 */
CONTENT,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * Query scope for foo.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    protected function scopeFoo(Builder $query)
    {
        // TODO: Filter the query by the 'foo' scope.
    }

    /**
     * Query scope for bar.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    protected function scopeBar(Builder $query)
    {
        // TODO: Filter the query by the 'bar' scope.
    }

    /**
     * Query scope for foo cougar.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    protected function scopeFooCougar(Builder $query)
    {
        // TODO: Filter the query by the 'fooCougar' scope.
    }
}
CONTENT,
            $model
        );

        $this->assertFileExistsInFilesystem($this->app->path('Scopes' . DS . 'User' . DS . 'CougarScope.php'));
    }

    public function test_does_not_creates_scopes()
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

        $this->assertDirectoryDoesNotExist($this->app->path('Scopes' . DS . 'User'));
    }

    public function test_creates_scopes_from_a_list_and_appends_scope_to_each()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                    'scopes' => [
                        'Foo',
                        'QuxScope'
                    ],
                ],
            ],
        ]);

        $this->shouldMockScopeFile(false);

        $this->artisan('larawiz:scaffold');

        $fooScope = $this->app->path('Scopes' . DS . 'User' . DS . 'FooScope.php');
        $quxScope = $this->app->path('Scopes' . DS . 'User' . DS . 'QuxScope.php');

        $this->assertFileExistsInFilesystem($fooScope);
        $this->assertFileExistsInFilesystem($quxScope);

        static::assertStringContainsString('use App\Models\User;', $this->filesystem->get($fooScope));
        static::assertStringContainsString('namespace App\Scopes\User;', $this->filesystem->get($fooScope));
        static::assertStringContainsString('class FooScope', $this->filesystem->get($fooScope));
        static::assertStringContainsString(
            '@param  \App\Models\User', $this->filesystem->get($fooScope)
        );
        static::assertStringContainsString(
            'public function apply(Builder $builder, Model $user)', $this->filesystem->get($fooScope)
        );
    }

    public function test_error_when_scopes_uses_namespace()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Scopes can only be set as class name, [Namespaced\FooScope] issued in [User].');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string'
                    ],
                    'scopes' => [
                        'Namespaced\Foo',
                    ],
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
