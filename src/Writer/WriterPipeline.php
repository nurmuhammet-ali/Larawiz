<?php

namespace Larawiz\Larawiz\Writer;

use Illuminate\Pipeline\Pipeline;

class WriterPipeline extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\WriteModels::class,
        Pipes\WriteMigrations::class,
        Pipes\WriteModelFactories::class,
        Pipes\WriteDatabaseSeeders::class,

//        Pipes\WriteHttpMiddleware::class,
//        Pipes\WriteHttpControllers::class,

//        Pipes\WriteAuthGates::class,
//        Pipes\WriteAuthPolicies::class,
//        Pipes\WriteRequestForms::class,
    ];
}
