<?php

namespace Larawiz\Larawiz\Scaffolding;

use Larawiz\Larawiz\ConsoleAwarePipeline;

class ScaffoldParserPipeline extends ConsoleAwarePipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\ParseDatabaseData::class,
//        Pipes\ParseHttpData::class,  // For Future reference
        Pipes\LexDatabaseData::class,
//        Pipes\LexHttpData::class,
        Pipes\CleanScaffoldRawData::class
    ];
}
