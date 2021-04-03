<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class HasManyThroughTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_guesses_target_and_through_model_from_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'userPosts' => 'hasManyThrough',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Country.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_countries_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $userPosts', $model);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\HasManyThrough|\App\Models\Post', $model);
        $this->assertStringContainsString('public function userPosts()', $model);
        $this->assertStringContainsString('return $this->hasManyThrough(Post::class, User::class);', $model);

        $this->assertStringNotContainsString("'userPosts'", $migration);
    }

    public function test_error_when_guessed_target_model_name_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [userFoo] relation in [Country] has non-existent models.');

        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'userFoo' => 'hasManyThrough',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_through_model_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [fooPosts] relation in [Country] has non-existent models.');

        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'fooPosts' => 'hasManyThrough',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_through_model_not_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [userPosts] relation in [Country] has non-existent models.');

        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'userPosts' => 'hasManyThrough:Post',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_receives_existent_models_with_different_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'posts' => 'hasManyThrough:Post,User',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Country.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_countries_table.php')
        );

        $this->assertStringContainsString(
            '@property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Post[] $posts', $model);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\HasManyThrough|\App\Models\Post', $model);
        $this->assertStringContainsString('public function posts()', $model);
        $this->assertStringContainsString('return $this->hasManyThrough(Post::class, User::class);', $model);

        $this->assertStringNotContainsString("'posts'", $migration);
    }

    public function test_error_when_target_model_has_no_belongs_to_through_model()
    {
        $this->expectExceptionMessage('For [userPosts] in [Country], the [Post] model must belong to [User].');
        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'userPosts' => 'hasManyThrough:Post,User',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_through_model_has_no_belongs_to_source_model()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('For [userPosts] in [Country], the [User] model must belong to [Country].');

        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'userPosts' => 'hasManyThrough:Post,User',
                ],
                'User'    => [
                    'name'    => 'string',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_doesnt_accepts_with_default()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("The [userPosts] relation type [hasManyThrough] in [Country] doesn't accepts [withDefault].");

        $this->mockDatabaseFile([
            'models' => [
                'Country' => [
                    'name'      => 'string',
                    'userPosts' => 'hasManyThrough:Post withDefault',
                ],
                'User'    => [
                    'name'    => 'string',
                    'country' => 'belongsTo',
                ],
                'Post'    => [
                    'title'  => 'string',
                    'user' => 'belongsTo',
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
