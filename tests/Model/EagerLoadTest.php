<?php

namespace Tests\Model;

use LogicException;
use Orchestra\Testbench\TestCase;
use Tests\CleansProjectFromScaffoldData;
use Tests\MocksDatabaseFile;
use Tests\RegistersPackage;

use const DIRECTORY_SEPARATOR as DS;

class EagerLoadTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;
    use MocksDatabaseFile;

    public function test_adds_eager_loads()
    {
        $this->mockDatabaseFile([
            'models' => [
                'Owner' => [
                    'car' => 'hasOne',
                ],
                'Car' => [
                    'owner' => 'belongsTo',
                    'mechanic' => 'hasOne',
                ],
                'Mechanic' => [
                    'columns' => [
                        'id' => null,
                        'car' => 'belongsTo',
                    ],
                    'with' => [
                        'car',
                        'car.owner',
                    ]
                ]
            ],
        ]);

        $this->artisan('larawiz:scaffold');

        $model = $this->filesystem->get($this->app->path('Models' . DS . 'Mechanic.php'));

        static::assertStringContainsString(<<<'CONTENT'
    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['car', 'car.owner'];
CONTENT
            ,
            $model
        );
    }

    public function test_error_if_eager_load_relation_doesnt_exists()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The eager load [car.invalid] of model [Mechanic] contains a non valid [invalid] relation.'
        );

        $this->mockDatabaseFile([
            'models' => [
                'Owner' => [
                    'car' => 'hasOne',
                ],
                'Car' => [
                    'owner' => 'belongsTo',
                    'mechanic' => 'hasOne',
                ],
                'Mechanic' => [
                    'columns' => [
                        'id' => null,
                        'car' => 'belongsTo',
                    ],
                    'with' => [
                        'car',
                        'car.owner',
                        'car.invalid',
                    ]
                ]
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
