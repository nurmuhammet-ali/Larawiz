<?php

namespace Tests\Model;

use Faker\Generator;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class FactoriesTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_automatically_creates_factory()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'name' => 'string',
                ],
                'Admin' => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileExistsInFilesystem($this->app->databasePath('factories' . DS . 'UserFactory.php'));
        $this->assertFileExistsInFilesystem($this->app->databasePath('factories' . DS . 'AdminFactory.php'));
    }

    public function test_doesnt_fill_id_or_autoincrement()
    {
        $increments = [
            'foo'   => 'id',
            'bar'   => 'increments',
            'quz'   => 'integerIncrements',
            'qux'   => 'tinyIncrements',
            'quux'  => 'smallIncrements',
            'quuz'  => 'mediumIncrements',
            'corge' => 'bigIncrements',
        ];

        foreach ($increments as $key => $increment) {
            $this->mockDatabaseFile([
                'models' => [
                    'User' => [
                        'columns' => [
                            'name' => 'string',
                            $key   => $increment,
                        ],
                    ],
                ],
            ]);

            $this->artisan('larawiz:scaffold');

            $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

            $this->assertStringNotContainsString("$key => ", $factory);
        }
    }

    public function test_doesnt_fill_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo'          => 'timestamp',
                    'bar'          => 'timestampTz',
                    'timestamps'   => null,
                    'timestampsTz' => null,
                    'softDeletes'  => null,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("    return [\n
        // ...\n
    ];\n", $factory);
    }

    public function test_detects_boolean()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'is_admin' => 'boolean',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertStringContainsString("'is_admin' => \$this->faker->boolean,",
            $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php')));
    }

    public function test_detects_uuid()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'uuid'        => null,
                    'public_uuid' => 'uuid',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertStringContainsString("'uuid' => \$this->faker->uuid,",
            $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php')));

        $this->assertStringContainsString("'public_uuid' => \$this->faker->uuid,",
            $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php')));
    }

    public function test_detects_date_time()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo'  => 'date',
                    'bar'  => 'dateTime',
                    'quz'  => 'dateTimeTz',
                    'qux'  => 'time',
                    'quux' => 'timeTz',
                    'quuz' => 'year',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo' => \$this->faker->date,", $factory);
        $this->assertStringContainsString("'bar' => \$this->faker->dateTime,", $factory);
        $this->assertStringContainsString("'quz' => \$this->faker->dateTime,", $factory);
        $this->assertStringContainsString("'qux' => \$this->faker->time,", $factory);
        $this->assertStringContainsString("'quux' => \$this->faker->time,", $factory);
        $this->assertStringContainsString("'quuz' => \$this->faker->year,", $factory);

    }

    public function test_detects_real_text()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'text',
                    'bar' => 'mediumText',
                    'quz' => 'longText',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo' => \$this->faker->realText(),", $factory);
        $this->assertStringContainsString("'bar' => \$this->faker->realText(),", $factory);
        $this->assertStringContainsString("'quz' => \$this->faker->realText(),", $factory);
    }

    public function test_detects_random_number()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo'  => 'integer',
                    'bar'  => 'unsignedInteger',
                    'quz'  => 'unsignedTinyInteger',
                    'qux'  => 'unsignedSmallInteger',
                    'quux' => 'unsignedMediumInteger',
                    'quuz' => 'unsignedBigInteger',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo' => \$this->faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'bar' => \$this->faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'quz' => \$this->faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'qux' => \$this->faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'quux' => \$this->faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'quuz' => \$this->faker->randomNumber(),", $factory);
    }

    public function test_detects_ipv4()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'ipAddress',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo' => \$this->faker->ipv4,", $factory);
    }

    public function test_detects_mac_address()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'macAddress',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo' => \$this->faker->macAddress,", $factory);
    }

    public function test_detects_mac_random_float()
    {

        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo'  => 'float',
                    'bar'  => 'double',
                    'quz'  => 'decimal',
                    'qux'  => 'unsignedFloat',
                    'quux' => 'unsignedDouble',
                    'quuz' => 'unsignedDecimal',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo' => \$this->faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'bar' => \$this->faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'quz' => \$this->faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'qux' => \$this->faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'quux' => \$this->faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'quuz' => \$this->faker->randomFloat(),", $factory);
    }

    public function test_detects_password_and_hashes_it_statically()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'name'     => 'string',
                    'password' => 'string',
                ],
                'Admin' => [
                    'name'     => 'string',
                    'password' => 'string',
                ],
            ],
        ]);

        $this->mock('hash')
            ->shouldReceive('make')
            ->with('secret')
            ->once()
            ->andReturn($hash = '$2y$10$yyBGGZeV0bJacIPlFNcFXOdNnPMghDIVRyQlAJei0gsV3ZuPojxVO');

        $this->artisan('larawiz:scaffold');

        $userFactory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));
        $adminFactory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'AdminFactory.php'));

        $this->assertStringContainsString("'password' => '$hash'", $userFactory);
        $this->assertStringContainsString("'password' => '$hash'", $adminFactory);
    }

    public function test_disables_factory()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name'     => 'string',
                        'password' => 'string',
                    ],
                    'factory' => false,
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $this->assertFileNotExistsInFilesystem($this->app->databasePath('factories' . DS . 'UserFactory.php'));
    }

    public function test_creates_factory_states()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name'     => 'string',
                        'password' => 'string',
                    ],
                    'factory' => [
                        'foo',
                        'bar',
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString(
            "public function foo()",
            $factory
        );
        $this->assertStringContainsString(
            "public function bar()",
            $factory
        );
    }

    public function test_adds_deleted_factory_state_when_using_soft_deletes()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'name'        => 'string',
                        'password'    => 'string',
                        'softDeletes' => null,
                    ],
                    'factory' => [
                        'foo',
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString(
            'public function foo()',
            $factory
        );
        $this->assertStringContainsString(
            'public function deleted()',
            $factory
        );
    }

    public function test_adds_empty_string_to_non_guessable_model_property()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'bar' => 'string',
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'bar' => '', // TODO: Add a random generated value for the [bar (string)] property", $factory);
    }

    public function test_uses_factory_provider_formatter_from_column_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo_bar'      => 'string',
                        'quz_qux_quuz' => 'string',
                        'bar' => 'string',
                    ],
                ],
            ],
        ]);

        $mock = $this->mock(Generator::class);

        $mock->shouldReceive('getProviders')
            ->andReturn([
                new class {
                    public function fooBar()
                    {

                    }

                    public function quzQuxQuuz($time = null)
                    {

                    }
                },
            ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringContainsString("'foo_bar' => \$this->faker->fooBar,", $factory);
        $this->assertStringContainsString("'quz_qux_quuz' => \$this->faker->quzQuxQuuz(),", $factory);
        $this->assertStringContainsString("'bar' => '', // TODO: Add a random generated value for the [bar (string)] property", $factory);
    }

    public function test_doesnt_fills_auto_incrementing()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'id' => null,
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'id' =>", $factory);
    }

    public function test_doesnt_fills_custom_auto_incrementing()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'bigIncrements',
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'foo' =>", $factory);
    }

    public function test_doesnt_fills_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'timestamps' => null,
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'created_at' =>", $factory);
        $this->assertStringNotContainsString("'updated_at' =>", $factory);
    }

    public function test_doesnt_fills_custom_timestamps()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'timestamp nullable',
                    ],
                    'timestamps' => [
                        'created_at' => 'foo',
                    ]
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'foo' =>", $factory);
    }

    public function test_doesnt_fills_timestamp()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo' => 'timestamp',
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'foo' =>", $factory);
    }

    public function test_doesnt_fill_soft_deletes()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'softDeletes' => null,
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("\$factory->define(User::class, function (Faker \$faker) {\n    return [\n        'deleted_at'", $factory);
    }

    public function test_doesnt_fills_custom_soft_deletes()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'softDeletes' => null,
                    ],
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("\$factory->define(User::class, function (Faker \$faker) {\n        return [\n        'deleted_at' =>", $factory);
    }

    public function test_doesnt_fills_belongs_to_relation()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'teams' => 'belongsTo'
                ],
                'Team' => [
                    'name' => 'string'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'team_id' =>", $factory);
    }

    public function test_doesnt_fills_morph_to_relation()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'teamable' => 'morphTo'
                ],
                'Company' => [
                    'name' => 'string'
                ],
                'Team' => [
                    'name' => 'string'
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'teamable' =>", $factory);
    }

    public function test_doesnt_fills_nullables()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'foo' => 'string nullable'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $factory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));

        $this->assertStringNotContainsString("'foo' =>", $factory);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
