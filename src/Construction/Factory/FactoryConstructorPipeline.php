<?php

namespace Larawiz\Larawiz\Construction\Factory;

use Illuminate\Pipeline\Pipeline;

class FactoryConstructorPipeline extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\SetModelFactory::class,
        Pipes\SetStates::class,
        Pipes\SetSoftDeleteState::class,
        Pipes\SetConfigure::class,
    ];
}
