<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class HasManyTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_error_when_target_model_has_no_belongs_to_parent_model_when_no_column_is_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The target model [User] for [posts] must contains a [belongsTo] relation.');

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name'  => 'name',
                    'posts' => 'hasMany',
                ],
                'Post' => [
                    'title' => 'name',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_target_model_has_incorrect_belongs_to_relation_without_column()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The target model [User] for [posts] must contains a [belongsTo] relation.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany'
                ],
                'Author' => [
                    'name' => 'string',
                ],
                'Post' => [
                    'title' => 'name',
                    'author' => 'belongsTo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_guesses_model_from_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        static::assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $posts', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Post', $model);
        static::assertStringContainsString('public function posts()', $model);
        static::assertStringContainsString('return $this->hasMany(Post::class);', $model);

        static::assertStringNotContainsString("'posts'", $migration);
    }

    public function test_error_when_cannot_guess_model()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [publications] relation of [User] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'publications' => 'hasMany'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }


    public function test_points_model_with_different_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'publications' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        static::assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $publications', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Post', $model);
        static::assertStringContainsString('public function publications()', $model);
        static::assertStringContainsString('return $this->hasMany(Post::class);', $model);

        static::assertStringNotContainsString("'posts'", $migration);
        static::assertStringNotContainsString("'publications'", $migration);
    }

    public function test_error_when_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [post] relation of [User] points to a non-existent [Publication] model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'post' => 'hasMany:Publication'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_doesnt_validates_manual_columns()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'publications' => 'hasMany:Post,title,name'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        static::assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $publications', $model);
        static::assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\HasMany|\App\Models\Post', $model);
        static::assertStringContainsString('public function publications()', $model);
        static::assertStringContainsString("return \$this->hasMany(Post::class, 'title', 'name');", $model);

        static::assertStringNotContainsString("'posts'", $migration);
        static::assertStringNotContainsString("'publications'", $migration);
    }

    public function test_error_when_includes_with_default()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [publications] relation type [hasMany] in [User] doesn't accepts [withDefault]");

        $this->mockDatabaseFile([
                  'models' => [
                      'User'     => [
                          'name' => 'name',
                          'publications' => 'hasMany:Post withDefault'
                      ],
                      'Post' => [
                          'title' => 'name',
                          'users' => 'belongsTo'
                      ]
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
