<?php

namespace Tests\Model\Relations;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class HasOneTest extends TestCase
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
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasOne'
                ],
                'Post' => [
                    'title' => 'name',
                ]
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
                    'posts' => 'hasOne'
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
                    'post' => 'hasOne'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\Post $post', $model);
        $this->assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Post', $model);
        $this->assertStringContainsString('public function post()', $model);
        $this->assertStringContainsString('return $this->hasOne(Post::class);', $model);

        $this->assertStringNotContainsString("'posts'", $migration);
    }

    public function test_error_when_cannot_guess_model()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [publication] relation of [User] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'publication' => 'hasOne'
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
                    'publication' => 'hasOne:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\Post $publication', $model);
        $this->assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Post', $model);
        $this->assertStringContainsString('public function publication()', $model);
        $this->assertStringContainsString('return $this->hasOne(Post::class);', $model);

        $this->assertStringNotContainsString("'posts'", $migration);
    }

    public function test_error_when_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [post] relation of [User] points to a non-existent [Publication] model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'post' => 'hasOne:Publication'
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
                    'publication' => 'hasOne:Post,title,name'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\Post $publication', $model);
        $this->assertStringContainsString('@return \Illuminate\Database\Eloquent\Relations\HasOne|\App\Post', $model);
        $this->assertStringContainsString('public function publication()', $model);
        $this->assertStringContainsString("return \$this->hasOne(Post::class, 'title', 'name');", $model);

        $this->assertStringNotContainsString("'posts'", $migration);
    }

    public function test_accepts_with_default()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'publication' => 'hasOne:Post withDefault'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('User.php'));

        $this->assertStringContainsString('return $this->hasOne(Post::class)->withDefault();', $model);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
