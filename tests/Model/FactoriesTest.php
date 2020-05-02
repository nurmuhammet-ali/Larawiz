<?php

namespace Tests\Model;

use Faker\Generator;
use Tests\RegistersPackage;
use Tests\MocksDatabaseFile;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
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

        $userFactory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php'));
        $adminFactory = $this->filesystem->get($this->app->databasePath('factories' . DS . 'AdminFactory.php'));

        $this->assertStringContainsString('// TODO: Review the Factory for the User model.', $userFactory);
        $this->assertStringContainsString('// TODO: Review the Factory for the Admin model.', $adminFactory);
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

        $this->assertStringContainsString("'is_admin' => \$faker->boolean,",
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

        $this->assertStringContainsString("'uuid' => \$faker->uuid,",
            $this->filesystem->get($this->app->databasePath('factories' . DS . 'UserFactory.php')));

        $this->assertStringContainsString("'public_uuid' => \$faker->uuid,",
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

        $this->assertStringContainsString("'foo' => \$faker->date,", $factory);
        $this->assertStringContainsString("'bar' => \$faker->dateTime,", $factory);
        $this->assertStringContainsString("'quz' => \$faker->dateTime,", $factory);
        $this->assertStringContainsString("'qux' => \$faker->time,", $factory);
        $this->assertStringContainsString("'quux' => \$faker->time,", $factory);
        $this->assertStringContainsString("'quuz' => \$faker->year,", $factory);

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

        $this->assertStringContainsString("'foo' => \$faker->realText(),", $factory);
        $this->assertStringContainsString("'bar' => \$faker->realText(),", $factory);
        $this->assertStringContainsString("'quz' => \$faker->realText(),", $factory);
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

        $this->assertStringContainsString("'foo' => \$faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'bar' => \$faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'quz' => \$faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'qux' => \$faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'quux' => \$faker->randomNumber(),", $factory);
        $this->assertStringContainsString("'quuz' => \$faker->randomNumber(),", $factory);
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

        $this->assertStringContainsString("'foo' => \$faker->ipv4,", $factory);
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

        $this->assertStringContainsString("'foo' => \$faker->macAddress,", $factory);
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

        $this->assertStringContainsString("'foo' => \$faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'bar' => \$faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'quz' => \$faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'qux' => \$faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'quux' => \$faker->randomFloat(),", $factory);
        $this->assertStringContainsString("'quuz' => \$faker->randomFloat(),", $factory);
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
            "\$factory->state(User::class, 'foo', function (Faker \$faker) {",
            $factory
        );
        $this->assertStringContainsString(
            "\$factory->state(User::class, 'bar', function (Faker \$faker) {",
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
            "\$factory->state(User::class, 'foo', function (Faker \$faker) {",
            $factory
        );
        $this->assertStringContainsString(
            "\$factory->state(User::class, 'deleted', function (Faker \$faker) {",
            $factory
        );
    }

    public function test_uses_factory_provider_formatter_from_column_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'User' => [
                    'columns' => [
                        'foo_bar'      => 'string',
                        'quz_qux_quuz' => 'string',
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

        $this->assertStringContainsString("'foo_bar' => \$faker->fooBar,", $factory);
        $this->assertStringContainsString("'quz_qux_quuz' => \$faker->quzQuxQuuz(),", $factory);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
