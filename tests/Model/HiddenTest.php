<?php

namespace Tests\Model;

use Illuminate\Support\Carbon;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class HiddenTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_quick_model_adds_sensible_columns_automatically()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'string',
                    'bar' => 'boolean',
                    'password' => 'string',
                    'password_foo' => 'string',
                    'private' => 'string',
                    'private_foo' => 'string',
                    'rememberToken' => null,
                    'hidden' => 'string',
                    'foo_hidden' => 'string',
                    'secret' => 'string',
                    'secret_foo' => 'string',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString("protected \$hidden = [
        'password',
        'password_foo',
        'private',
        'private_foo',
        'remember_token',
        'hidden',
        'foo_hidden',
        'secret',
        'secret_foo',
    ];", $model);

        static::assertStringContainsString("\$table->string('foo');", $migration);
        static::assertStringContainsString("\$table->boolean('bar');", $migration);
        static::assertStringContainsString("\$table->string('password');", $migration);
        static::assertStringContainsString("\$table->string('password_foo');", $migration);
        static::assertStringContainsString("\$table->string('private');", $migration);
        static::assertStringContainsString("\$table->string('private_foo');", $migration);
        static::assertStringContainsString("\$table->rememberToken();", $migration);
        static::assertStringContainsString("\$table->string('hidden');", $migration);
        static::assertStringContainsString("\$table->string('foo_hidden');", $migration);
        static::assertStringContainsString("\$table->string('secret');", $migration);
        static::assertStringContainsString("\$table->string('secret_foo');", $migration);
    }

    public function test_custom_model_adds_sensible_columns_automatically()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'string',
                        'bar' => 'boolean',
                        'password' => 'string',
                        'password_foo' => 'string',
                        'private' => 'string',
                        'private_foo' => 'string',
                        'rememberToken' => null,
                        'hidden' => 'string',
                        'foo_hidden' => 'string',
                        'secret' => 'string',
                        'secret_foo' => 'string',
                    ],
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString("protected \$hidden = [
        'password',
        'password_foo',
        'private',
        'private_foo',
        'remember_token',
        'hidden',
        'foo_hidden',
        'secret',
        'secret_foo',
    ];", $model);

        static::assertStringContainsString("\$table->string('foo');", $migration);
        static::assertStringContainsString("\$table->boolean('bar');", $migration);
        static::assertStringContainsString("\$table->string('password');", $migration);
        static::assertStringContainsString("\$table->string('password_foo');", $migration);
        static::assertStringContainsString("\$table->string('private');", $migration);
        static::assertStringContainsString("\$table->string('private_foo');", $migration);
        static::assertStringContainsString("\$table->rememberToken();", $migration);
        static::assertStringContainsString("\$table->string('hidden');", $migration);
        static::assertStringContainsString("\$table->string('foo_hidden');", $migration);
        static::assertStringContainsString("\$table->string('secret');", $migration);
        static::assertStringContainsString("\$table->string('secret_foo');", $migration);
    }

    public function test_custom_model_doesnt_adds_hidden()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'string',
                        'bar' => 'boolean',
                        'password' => 'string',
                        'password_foo' => 'string',
                        'private' => 'string',
                        'private_foo' => 'string',
                        'rememberToken' => null,
                        'hidden' => 'string',
                        'foo_hidden' => 'string',
                        'secret' => 'string',
                        'secret_foo' => 'string',
                    ],
                    'hidden' => false,
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringNotContainsString("protected \$hidden", $model);

        static::assertStringContainsString("\$table->string('foo');", $migration);
        static::assertStringContainsString("\$table->boolean('bar');", $migration);
        static::assertStringContainsString("\$table->string('password');", $migration);
        static::assertStringContainsString("\$table->string('password_foo');", $migration);
        static::assertStringContainsString("\$table->string('private');", $migration);
        static::assertStringContainsString("\$table->string('private_foo');", $migration);
        static::assertStringContainsString("\$table->rememberToken();", $migration);
        static::assertStringContainsString("\$table->string('hidden');", $migration);
        static::assertStringContainsString("\$table->string('foo_hidden');", $migration);
        static::assertStringContainsString("\$table->string('secret');", $migration);
        static::assertStringContainsString("\$table->string('secret_foo');", $migration);
    }

    public function test_custom_model_add_custom_hidden()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'string',
                        'bar' => 'string',
                        'quz' => 'string',
                    ],
                    'hidden' => [
                        'bar', 'quz', 'quuz'
                    ]
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'User.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_users_table.php'));

        static::assertStringContainsString("protected \$hidden = ['bar', 'quz', 'quuz'];", $model);

        static::assertStringContainsString("\$table->string('foo');", $migration);
        static::assertStringContainsString("\$table->string('bar');", $migration);
        static::assertStringContainsString("\$table->string('quz');", $migration);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
