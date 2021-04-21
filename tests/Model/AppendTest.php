<?php

namespace Tests\Model;

use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class AppendTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_model_doesnt_adds_appends()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringNotContainsString("protected \$appends", $model);
    }

    public function test_model_sets_appends()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'append' => [
                        'foo' => 'integer'
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 * @property-read int $foo
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    protected $append = ['foo'];
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * Returns the 'foo' attribute.
     *
     * @return int
     */
    protected function getFooAttribute()
    {
        // TODO: Code the 'foo' getter.
    }
CONTENT
            ,
            $model
        );
    }

    public function test_appends_corrects_primitive()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'append' => [
                        'foo' => 'collection',
                        'bar' => 'date',
                        'baz' => 'datetime',
                        'quz' => 'int',
                        'qux' => 'integer',
                        'quux' => 'float',
                        'corge' => 'decimal',
                        'grault' => 'point',
                        'garply' => 'bool',
                        'waldo' => 'boolean',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 * @property-read \Illuminate\Support\Collection $foo
 * @property-read \Illuminate\Support\Carbon $bar
 * @property-read \Illuminate\Support\Carbon $baz
 * @property-read int $quz
 * @property-read int $qux
 * @property-read float $quux
 * @property-read float $corge
 * @property-read float $grault
 * @property-read bool $garply
 * @property-read bool $waldo
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    protected $append = ['foo', 'bar', 'baz', 'quz', 'qux', 'quux', 'corge', 'grault', 'garply', 'waldo'];
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * Returns the 'foo' attribute.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getFooAttribute()
    {
        // TODO: Code the 'foo' getter.
    }

    /**
     * Returns the 'bar' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getBarAttribute()
    {
        // TODO: Code the 'bar' getter.
    }

    /**
     * Returns the 'baz' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getBazAttribute()
    {
        // TODO: Code the 'baz' getter.
    }

    /**
     * Returns the 'quz' attribute.
     *
     * @return int
     */
    protected function getQuzAttribute()
    {
        // TODO: Code the 'quz' getter.
    }

    /**
     * Returns the 'qux' attribute.
     *
     * @return int
     */
    protected function getQuxAttribute()
    {
        // TODO: Code the 'qux' getter.
    }

    /**
     * Returns the 'quux' attribute.
     *
     * @return float
     */
    protected function getQuuxAttribute()
    {
        // TODO: Code the 'quux' getter.
    }

    /**
     * Returns the 'corge' attribute.
     *
     * @return float
     */
    protected function getCorgeAttribute()
    {
        // TODO: Code the 'corge' getter.
    }

    /**
     * Returns the 'grault' attribute.
     *
     * @return float
     */
    protected function getGraultAttribute()
    {
        // TODO: Code the 'grault' getter.
    }

    /**
     * Returns the 'garply' attribute.
     *
     * @return bool
     */
    protected function getGarplyAttribute()
    {
        // TODO: Code the 'garply' getter.
    }

    /**
     * Returns the 'waldo' attribute.
     *
     * @return bool
     */
    protected function getWaldoAttribute()
    {
        // TODO: Code the 'waldo' getter.
    }
}
CONTENT
            ,
            $model
        );
    }

    public function test_appends_corrects_date_primitive()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'append' => [
                        'foo' => 'date',
                        'bar' => 'datetime',
                        'baz' => 'datetimeTz',
                        'quz' => 'dateTime',
                        'qux' => 'dateTimeTz',
                        'quux' => 'timestamp',
                        'corge' => 'timestampTz',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 * @property-read \Illuminate\Support\Carbon $foo
 * @property-read \Illuminate\Support\Carbon $bar
 * @property-read \Illuminate\Support\Carbon $baz
 * @property-read \Illuminate\Support\Carbon $quz
 * @property-read \Illuminate\Support\Carbon $qux
 * @property-read \Illuminate\Support\Carbon $quux
 * @property-read \Illuminate\Support\Carbon $corge
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    protected $append = ['foo', 'bar', 'baz', 'quz', 'qux', 'quux', 'corge'];
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * Returns the 'foo' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getFooAttribute()
    {
        // TODO: Code the 'foo' getter.
    }

    /**
     * Returns the 'bar' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getBarAttribute()
    {
        // TODO: Code the 'bar' getter.
    }

    /**
     * Returns the 'baz' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getBazAttribute()
    {
        // TODO: Code the 'baz' getter.
    }

    /**
     * Returns the 'quz' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getQuzAttribute()
    {
        // TODO: Code the 'quz' getter.
    }

    /**
     * Returns the 'qux' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getQuxAttribute()
    {
        // TODO: Code the 'qux' getter.
    }

    /**
     * Returns the 'quux' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getQuuxAttribute()
    {
        // TODO: Code the 'quux' getter.
    }

    /**
     * Returns the 'corge' attribute.
     *
     * @return \Illuminate\Support\Carbon
     */
    protected function getCorgeAttribute()
    {
        // TODO: Code the 'corge' getter.
    }
}
CONTENT
            ,
            $model
        );
    }

    public function test_appends_accepts_class()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'append' => [
                        'foo' => 'Illuminate\Support\Fluent',
                        'bar' => '\Illuminate\Support\Collection',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 * @property-read \Illuminate\Support\Fluent $foo
 * @property-read \Illuminate\Support\Collection $bar
CONTENT
            ,
            $model
        );

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * Returns the 'foo' attribute.
     *
     * @return \Illuminate\Support\Fluent
     */
    protected function getFooAttribute()
    {
        // TODO: Code the 'foo' getter.
    }

    /**
     * Returns the 'bar' attribute.
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getBarAttribute()
    {
        // TODO: Code the 'bar' getter.
    }
}
CONTENT
            ,
            $model
        );
    }

    public function test_error_if_class_append_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "The DoesntExistsClass\DummyNamespace\Go class doesn't exists for the appended [foo] of [User]"
        );

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'append' => [
                        'foo' => 'DoesntExistsClass\DummyNamespace\Go',
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
