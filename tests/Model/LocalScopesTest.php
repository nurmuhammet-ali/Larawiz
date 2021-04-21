<?php

namespace Tests\Model;

use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class LocalScopesTest extends TestCase
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
                    'localScopes' => [
                        'foo',
                        'bar',
                        'foo',
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
        // $query
    }

    /**
     * Query scope for bar.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    protected function scopeBar(Builder $query)
    {
        // $query
    }
}
CONTENT,
            $model
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
