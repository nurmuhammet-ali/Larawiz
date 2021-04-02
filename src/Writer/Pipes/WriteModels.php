<?php

namespace Larawiz\Larawiz\Writer\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Construction\Model\ModelConstructorPipeline;
use Larawiz\Larawiz\Scaffold;

class WriteModels
{
    /**
     * Model Constructor Pipeline.
     *
     * @var \Larawiz\Larawiz\Construction\Model\ModelConstructorPipeline
     */
    protected $pipeline;

    /**
     * WriteDatabaseModels constructor.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstructorPipeline  $pipeline
     */
    public function __construct(ModelConstructorPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    /**
     * Handle writing the scaffold files.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        foreach ($scaffold->database->models as $model) {
            $this->pipeline->send(new ModelConstruction([
                'model' => $model
            ]))->thenReturn();
        }

        return $next($scaffold);
    }
}
