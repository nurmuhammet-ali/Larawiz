<?php

namespace Tests\Model;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class ColumnCastTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_overrides_basic_cast_type()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'string',
                        'bar' => 'string',
                        'baz' => 'string',
                        'qux' => 'string',
                        'quz' => 'string',
                        'quux' => 'string',
                        'quuz' => 'string',
                        'cougar' => 'string',
                        'date' => 'string',
                    ],
                    'casts' => [
                        'foo' => 'string',
                        'bar' => 'int',
                        'baz' => 'float',
                        'qux' => 'bool',
                        'quz' => 'array',
                        'quux' => 'collection',
                        'quuz' => 'array',
                        'cougar' => 'object',
                        'date' => 'date',
                    ]
                ],
            ],
        ]);

        $this->shouldMockCastFile(false);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->path('Casts' . DS . 'string.php'));
        $this->assertFileNotExistsInFilesystem($this->app->path('Casts' . DS . 'int.php'));
        $this->assertFileNotExistsInFilesystem($this->app->path('Casts' . DS . 'float.php'));
        $this->assertFileNotExistsInFilesystem($this->app->path('Casts' . DS . 'bool.php'));
        $this->assertFileNotExistsInFilesystem($this->app->path('Casts' . DS . 'array.php'));

        $content = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 * @property string $foo
 * @property int $bar
 * @property float $baz
 * @property bool $qux
 * @property array $quz
 * @property \Illuminate\Support\Collection $quux
 * @property array $quuz
 * @property object $cougar
 * @property \Illuminate\Support\Carbon $date
CONTENT
            ,
            $content);

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'foo' => 'string',
        'bar' => 'int',
        'baz' => 'float',
        'qux' => 'bool',
        'quz' => 'array',
        'quux' => '\Illuminate\Support\Collection',
        'quuz' => 'array',
        'cougar' => 'object',
        'date' => '\Illuminate\Support\Carbon',
    ];
CONTENT
            ,
            $content);
    }

    public function test_quick_model_adds_custom_cast()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'string'
                    ],
                    'casts' => [
                        'foo' => 'MyCustomCast'
                    ]
                ],
                'Foo\Admin'  => [
                    'columns' => [
                        'bar' => 'string'
                    ],
                    'casts' => [
                        'bar' => 'MyCustomCast'
                    ]
                ]
            ],
        ]);

        $this->shouldMockCastFile(false);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $content = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));

        static::assertStringContainsString(<<<'CONTENT'
 * @property string $foo
CONTENT
            ,
            $content);

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = ['foo' => MyCustomCast::class];
CONTENT
            ,
            $content);

        $this->assertFileExistsInFilesystem($this->app->path('Casts' . DS . 'MyCustomCast.php'));

        $content = $this->filesystem->get($this->app->path('Casts' . DS . 'MyCustomCast.php'));

        $this->assertEquals(<<<'CONTENT'
<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class MyCustomCast extends CastAttributes
{
    /**
     * Cast the given value.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  mixed  $value
     * @param  array  $attributes
     * @return array
     */
    public function get($model, $key, $value, $attributes)
    {
        // TODO: Update the casting of the value.
        return $key;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @param  string  $key
     * @param  array  $value
     * @param  array  $attributes
     * @return string
     */
    public function set($model, $key, $value, $attributes)
    {
        // TODO: Update the casting of the value.
        return $key;
    }
}

CONTENT
            ,$content);
    }

    public function test_model_has_set_custom_cast_and_overrides_default_cast()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'string'
                    ],
                    'casts' => [
                        'foo' => 'MyCustomCast'
                    ]
                ],
                'Foo\Admin'  => [
                    'columns' => [
                        'bar' => 'json'
                    ],
                    'casts' => [
                        'bar' => 'MyCustomCast'
                    ]
                ]
            ],
        ]);

        $this->shouldMockCastFile(false);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        static::assertStringContainsString(
            "protected \$casts = ['foo' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            "use App\Casts\MyCustomCast;",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            "protected \$casts = ['bar' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' .DS . 'Admin.php'))
        );

        static::assertStringContainsString(
            "use App\Casts\MyCustomCast;",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' .DS . 'Admin.php'))
        );
    }

    public function test_adds_custom_cast_and_overrides_type()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Client'  => [
                    'columns' => [
                        'foo' => 'unsignedTinyInteger'
                    ],
                    'casts' => [
                        'foo' => 'MyCustomCast'
                    ]
                ],
                'User'  => [
                    'columns' => [
                        'foo' => 'string'
                    ],
                    'casts' => [
                        'foo' => 'MyCustomCast \DateTimeInterface'
                    ]
                ],
                'Foo\Admin'  => [
                    'columns' => [
                        'bar' => 'json'
                    ],
                    'casts' => [
                        'bar' => 'MyCustomCast int'
                    ]
                ]
            ],
        ]);

        $this->shouldMockCastFile(false);

        $this->artisan('larawiz:scaffold');

        static::assertStringContainsString(
            "* @property int \$foo",
            $this->filesystem->get($this->app->path('Models' . DS . 'Client.php'))
        );

        static::assertStringContainsString(
            "protected \$casts = ['foo' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'Client.php'))
        );

        static::assertStringContainsString(
            "* @property \DateTimeInterface \$foo",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            "protected \$casts = ['foo' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            "* @property int \$bar",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' .DS . 'Admin.php'))
        );

        static::assertStringContainsString(
            "protected \$casts = ['bar' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' .DS . 'Admin.php'))
        );
    }

    public function test_adds_external_cast()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'columns' => [
                        'foo' => 'integer'
                    ],
                    'casts' => [
                        'foo' => 'Tests\Model\DummyCustomCast'
                    ]
                ],
                'Foo\Admin'  => [
                    'columns' => [
                        'bar' => 'json'
                    ],
                    'casts' => [
                        'bar' => 'Tests\Model\DummyCustomCast string'
                    ]
                ]
            ],
        ]);

        $this->shouldMockCastFile(false);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        static::assertStringContainsString(
            " * @property int \$foo",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            "use Tests\Model\DummyCustomCast;",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        static::assertStringContainsString(
            "protected \$casts = ['foo' => DummyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );


        static::assertStringContainsString(
            "use Tests\Model\DummyCustomCast;",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' . DS . 'Admin.php'))
        );

        static::assertStringContainsString(
            " * @property string \$bar",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' . DS . 'Admin.php'))
        );

        static::assertStringContainsString(
            "protected \$casts = ['bar' => DummyCustomCast::class]",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' . DS . 'Admin.php'))
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}


class DummyCustomCast implements CastsAttributes {

    public function get($model, string $key, $value, array $attributes)
    {
        // TODO: Implement get() method.
    }

    public function set($model, string $key, $value, array $attributes)
    {
        // TODO: Implement set() method.
    }
}
