<?php

namespace Tests\Model;

use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;

class UserTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_is_normal_model()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'name',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            'class User extends Model',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_quick_model_sets_model_as_user_when_password_is_issued()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'name',
                    'password' => 'string'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            'class User extends Authenticatable',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_quick_model_sets_model_as_user_when_remember_token_is_issued()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'name',
                    'rememberToken' => null
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            'class User extends Authenticatable',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_quick_model_sets_model_as_user_when_password_and_remember_token_is_issued()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'name',
                    'password' => 'string',
                    'rememberToken' => null
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            'class User extends Authenticatable',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_model_can_set_type_to_user()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'name',
                    ],
                    'type' => 'user'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            'class User extends Authenticatable',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_user_model_sets_foundation_user_and_traits()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'name',
                    'password' => 'string',
                    'rememberToken' => null
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            'use Illuminate\Contracts\Auth\MustVerifyEmail;',
            $this->filesystem->get($this->app->path('User.php'))
        );
        $this->assertStringContainsString(
            'use Illuminate\Foundation\Auth\User as Authenticatable;',
            $this->filesystem->get($this->app->path('User.php'))
        );
        $this->assertStringContainsString(
            'use Illuminate\Notifications\Notifiable;',
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    public function test_user_model_includes_password_mutator_if_present()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'name',
                    'password' => 'string',
                    'rememberToken' => null
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->path('User.php'));
        $this->assertStringContainsString(
            "public function setPasswordAttribute(\$password)
    {
        \$this->attributes['password'] = app('hash')->make(\$password);
    }",
            $this->filesystem->get($this->app->path('User.php'))
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
