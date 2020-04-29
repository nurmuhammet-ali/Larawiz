<?php

namespace Tests\Model\Relations;

use LogicException;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class BelongsToTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_error_when_model_does_not_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [users] relation in [Post] points to non-existent [Foo] model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'users' => 'belongsTo:Foo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_model_cannot_be_guessed()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [authors] relation of [Post] must have a target model.');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'authors' => 'belongsTo'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_guesses_model_name_from_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        $this->assertStringContainsString('@property-read \App\User $user', $model);
        $this->assertStringContainsString('return $this->belongsTo(User::class);', $model);
        $this->assertStringContainsString(
            "\$table->unsignedBigInteger('user_id'); // Created for [user] relation.", $migration
        );
    }

    public function test_with_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        $this->assertStringContainsString('@property-read \App\User $user', $model);
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        $this->assertStringContainsString(
            "\$table->string('user_name'); // Created for [user] relation.", $migration
        );
    }

    public function test_error_when_column_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The relation [user] references the [bar] column in the [User] but it doesn\'t exists');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'name',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,foo_bar'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_can_not_guess_without_primary_key()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [user] relation in [Post] needs a column of [User].');

        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'columns' => [
                        'name' => 'string',
                        'posts' => 'hasMany:Post'
                    ]
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_creates_nullable_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name nullable'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\User $user', $model);
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        $this->assertStringContainsString(
            "\$table->string('user_name')->nullable(); // Created for [user] relation.", $migration
        );
    }

    public function test_creates_index_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name index'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        $this->assertStringContainsString('@property-read \App\User $user', $model);
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        $this->assertStringContainsString(
            "\$table->string('user_name')->index(); // Created for [user] relation.", $migration
        );
    }

    public function test_creates_unique_column()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name unique'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        $this->assertStringContainsString('@property-read \App\User $user', $model);
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'user_name');", $model);
        $this->assertStringContainsString(
            "\$table->string('user_name')->unique(); // Created for [user] relation.", $migration
        );
    }

    public function test_accepts_with_default_and_nullable()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'     => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post'
                ],
                'Post' => [
                    'title' => 'name',
                    'user' => 'belongsTo:User,user_name nullable withDefault'
                ]
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Post.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_posts_table.php')
        );

        $this->assertStringContainsString('@property-read \App\User $user', $model);
        $this->assertStringContainsString("return \$this->belongsTo(User::class, 'user_name')->withDefault();", $model);
        $this->assertStringContainsString(
            "\$table->string('user_name')->nullable(); // Created for [user] relation.", $migration
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
