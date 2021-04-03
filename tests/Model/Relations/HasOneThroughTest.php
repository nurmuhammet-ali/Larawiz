<?php

namespace Tests\Model\Relations;

use Illuminate\Support\Carbon;
use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class HasOneThroughTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_guesses_target_and_through_model_from_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'carOwner' => 'hasOneThrough',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car'   => 'belongsTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Mechanic.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_mechanics_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\Models\Owner $carOwner', $model);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\HasOneThrough|\App\Models\Owner', $model);
        $this->assertStringContainsString('public function carOwner()', $model);
        $this->assertStringContainsString('return $this->hasOneThrough(Owner::class, Car::class);', $model);

        $this->assertStringNotContainsString("'owner'", $migration);
    }

    public function test_error_when_guessed_target_model_name_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [fooOwner] relation in [Mechanic] has non-existent models.');

        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'fooOwner' => 'hasOneThrough',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car'   => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_through_model_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [carFoo] relation in [Mechanic] has non-existent models.');

        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'carFoo' => 'hasOneThrough',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car'   => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_through_model_not_set()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The [carFoo] relation in [Mechanic] has non-existent models.');

        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'carFoo' => 'hasOneThrough:Owner',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car'   => 'belongsTo',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_receives_existent_models_with_different_relation_name()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'owner' => 'hasOneThrough:Owner,Car',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car'   => 'belongsTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Mechanic.php'));
        $migration = $this->filesystem->get(
            $this->app->databasePath('migrations' . DS . '2020_01_01_163000_create_mechanics_table.php')
        );

        $this->assertStringContainsString('@property-read null|\App\Models\Owner $owner', $model);
        $this->assertStringContainsString(
            '@return \Illuminate\Database\Eloquent\Relations\HasOneThrough|\App\Models\Owner', $model);
        $this->assertStringContainsString('public function owner()', $model);
        $this->assertStringContainsString('return $this->hasOneThrough(Owner::class, Car::class);', $model);

        $this->assertStringNotContainsString("'owner'", $migration);
    }

    public function test_error_when_target_model_has_no_belongs_to_through_model()
    {
        $this->expectExceptionMessage('For [owner] in [Mechanic], the [Owner] model must belong to [Car].');
        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'owner' => 'hasOneThrough:Owner,Car',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_error_when_through_model_has_no_belongs_to_source_model()
    {
        $this->expectExceptionMessage('For [owner] in [Mechanic], the [Car] model must belong to [Mechanic].');
        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'owner' => 'hasOneThrough:Owner,Car',
                ],
                'Car'      => [
                    'name'     => 'string',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car' => 'belongsTo'
                ],
            ],
        ]);

        $this->artisan('larawiz:scaffold');
    }

    public function test_accepts_with_default()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Mechanic' => [
                    'name'     => 'string',
                    'owner' => 'hasOneThrough:Owner,Car withDefault',
                ],
                'Car'      => [
                    'name'     => 'string',
                    'mechanic' => 'belongsTo',
                ],
                'Owner'    => [
                    'title' => 'string',
                    'car'   => 'belongsTo',
                ],
            ],
        ]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 16:30:00'));

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Mechanic.php'));

        $this->assertStringContainsString(
            'return $this->hasOneThrough(Owner::class, Car::class)->withDefault();', $model);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
