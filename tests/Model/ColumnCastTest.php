<?php

namespace Tests\Model;

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

        $this->assertStringContainsString(
            "protected \$casts = ['foo' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        $this->assertStringContainsString(
            "use App\Casts\MyCustomCast;",
            $this->filesystem->get($this->app->path('Models' . DS . 'User.php'))
        );

        $this->assertStringContainsString(
            "protected \$casts = ['bar' => MyCustomCast::class];",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' .DS . 'Admin.php'))
        );

        $this->assertStringContainsString(
            "use App\Casts\MyCustomCast;",
            $this->filesystem->get($this->app->path('Models' . DS . 'Foo' .DS . 'Admin.php'))
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
