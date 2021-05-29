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


        static::assertEquals(<<<'CONTENT'
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->userName,
        ];
    }

    /**
     * Configure the model factory
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterMaking(function (User $user) {
            // TODO: Add after making configuration.
        })->afterCreating(function (User $user) {
            // TODO: Add after creating configuration.
        });
    }
}

CONTENT
            ,
            $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'))
        );
    }

    public function test_sets_model_property()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User'  => [
                    'name' => 'string',
                ],
                'Foo\Bar' => [
                    'name' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $user = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));
        $bar = $this->filesystem->get($this->app->databasePath('factories' . DS . 'BarFactory.php'));

        static::assertStringContainsString('protected $model = User::class;', $user);
        static::assertStringContainsString('protected $model = Bar::class;', $bar);
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

            static::assertStringNotContainsString("$key => ", $factory);
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

        static::assertStringNotContainsString("    return [\n
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

        static::assertStringContainsString("'is_admin' => \$this->faker->boolean,",
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

        static::assertStringContainsString("'uuid' => \$this->faker->uuid,",
            $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php')));

        static::assertStringContainsString("'public_uuid' => \$this->faker->uuid,",
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

        static::assertStringContainsString("'foo' => \$this->faker->date,", $factory);
        static::assertStringContainsString("'bar' => \$this->faker->dateTime,", $factory);
        static::assertStringContainsString("'quz' => \$this->faker->dateTime,", $factory);
        static::assertStringContainsString("'qux' => \$this->faker->time,", $factory);
        static::assertStringContainsString("'quux' => \$this->faker->time,", $factory);
        static::assertStringContainsString("'quuz' => \$this->faker->year,", $factory);

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

        static::assertStringContainsString("'foo' => \$this->faker->realText(),", $factory);
        static::assertStringContainsString("'bar' => \$this->faker->realText(),", $factory);
        static::assertStringContainsString("'quz' => \$this->faker->realText(),", $factory);
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

        static::assertStringContainsString("'foo' => \$this->faker->randomNumber(),", $factory);
        static::assertStringContainsString("'bar' => \$this->faker->randomNumber(),", $factory);
        static::assertStringContainsString("'quz' => \$this->faker->randomNumber(),", $factory);
        static::assertStringContainsString("'qux' => \$this->faker->randomNumber(),", $factory);
        static::assertStringContainsString("'quux' => \$this->faker->randomNumber(),", $factory);
        static::assertStringContainsString("'quuz' => \$this->faker->randomNumber(),", $factory);
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

        static::assertStringContainsString("'foo' => \$this->faker->ipv4,", $factory);
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

        static::assertStringContainsString("'foo' => \$this->faker->macAddress,", $factory);
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

        static::assertStringContainsString("'foo' => \$this->faker->randomFloat(),", $factory);
        static::assertStringContainsString("'bar' => \$this->faker->randomFloat(),", $factory);
        static::assertStringContainsString("'quz' => \$this->faker->randomFloat(),", $factory);
        static::assertStringContainsString("'qux' => \$this->faker->randomFloat(),", $factory);
        static::assertStringContainsString("'quux' => \$this->faker->randomFloat(),", $factory);
        static::assertStringContainsString("'quuz' => \$this->faker->randomFloat(),", $factory);
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

        static::assertStringContainsString("'password' => '$hash'", $userFactory);
        static::assertStringContainsString("'password' => '$hash'", $adminFactory);
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

        static::assertStringContainsString(
            "public function foo()",
            $factory
        );
        static::assertStringContainsString(
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

        static::assertStringContainsString(
            'public function foo()',
            $factory
        );
        static::assertStringContainsString(
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

        static::assertStringContainsString("'bar' => '', // TODO: Add a random generated value for the [bar (string)] property", $factory);
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

        static::assertStringContainsString("'foo_bar' => \$this->faker->fooBar,", $factory);
        static::assertStringContainsString("'quz_qux_quuz' => \$this->faker->quzQuxQuuz(),", $factory);
        static::assertStringContainsString("'bar' => '', // TODO: Add a random generated value for the [bar (string)] property", $factory);
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

        static::assertStringNotContainsString("'id' =>", $factory);
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

        static::assertStringNotContainsString("'foo' =>", $factory);
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

        static::assertStringNotContainsString("'created_at' =>", $factory);
        static::assertStringNotContainsString("'updated_at' =>", $factory);
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

        static::assertStringNotContainsString("'foo' =>", $factory);
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

        static::assertStringNotContainsString("'foo' =>", $factory);
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

        static::assertStringNotContainsString("\$factory->define(User::class, function (Faker \$faker) {\n    return [\n        'deleted_at'", $factory);
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

        static::assertStringNotContainsString("\$factory->define(User::class, function (Faker \$faker) {\n        return [\n        'deleted_at' =>", $factory);
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

        static::assertStringNotContainsString("'team_id' =>", $factory);
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

        static::assertStringNotContainsString("'teamable' =>", $factory);
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

        static::assertStringNotContainsString("'foo' =>", $factory);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
