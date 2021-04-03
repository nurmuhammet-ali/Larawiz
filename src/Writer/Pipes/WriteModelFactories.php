<?php

namespace Larawiz\Larawiz\Writer\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Factory\FactoryConstruction;
use Larawiz\Larawiz\Construction\Factory\FactoryConstructorPipeline;
use Larawiz\Larawiz\Scaffold;

class WriteModelFactories
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
     * @param  \Larawiz\Larawiz\Construction\Factory\FactoryConstructorPipeline  $pipeline
     */
    public function __construct(FactoryConstructorPipeline $pipeline)
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
            // If the model is using factories, we will create it.
            if ($model->useFactory) {
                $this->pipeline->send(new FactoryConstruction(['model' => $model]))->thenReturn();
            }
        }

        return $next($scaffold);
    }
}
