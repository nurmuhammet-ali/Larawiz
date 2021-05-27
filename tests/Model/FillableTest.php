<?php

namespace Tests\Model;

use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class FillableTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_model_adds_fillable_automatically()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DIRECTORY_SEPARATOR . 'User.php');

        $this->assertStringContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
    }

    public function test_model_does_not_fill_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'foo' => 'timestamp',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
    }

    public function test_model_does_not_fill_booleans()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'foo' => 'boolean',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
    }

    public function test_model_does_not_fill_relations_columns()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'posts' => 'hasMany:Post',
                ],
                'Post' => [
                    'author' => 'belongsTo:User'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
    }

    public function test_model_does_not_fill_soft_deletes()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'name' => 'string',
                    'softDeletes' => null,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
    }

    public function test_model_does_not_fill_auto_incrementing_columns()
    {
        $increments = [
            'alpha' => 'increments',
            'beta' => 'integerIncrements',
            'charlie' => 'tinyIncrements',
            'delta' => 'smallIncrements',
            'foxtrot' => 'mediumIncrements',
            'echo' => 'bigIncrements',
        ];

        foreach ($increments as $key => $increment) {

            $this->mockDatabaseFile([
                'models' => [
                    'User' => [
                        'columns' => [
                            'name' => 'string',
                            $key => $increment,
                        ]
                    ],
                ],
            ]);

            $this->artisan('larawiz:scaffold');

            $model = $this->app->path('Models' . DS . 'User.php');

            $this->assertStringContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
        }

    }

    public function test_model_disables_fillable()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                    ],
                    'fillable' => false,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringNotContainsString("protected \$fillable = ['name'];", $this->filesystem->get($model));
    }

    public function test_model_overrides_fillable_list()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                        'email' => 'email',
                        'age' => 'int'
                    ],
                    'fillable' => [
                        'name',
                        'age'
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringContainsString("protected \$fillable = ['name', 'age'];", $this->filesystem->get($model));
    }

    public function test_no_error_when_fillable_column_doesnt_exists()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name' => 'string',
                        'email' => 'email',
                        'age' => 'int'
                    ],
                    'fillable' => [
                        'doesnt_exists',
                        'age'
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->app->path('Models' . DS . 'User.php');

        $this->assertStringContainsString(
            "protected \$fillable = ['doesnt_exists', 'age'];", $this->filesystem->get($model)
        );
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
