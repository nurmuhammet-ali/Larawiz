<?php

namespace Larawiz\Larawiz\Writer\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Construction\Controller\ControllerConstrution;
use Larawiz\Larawiz\Construction\Controller\ControllerConstructorPipeline;

class WriteHttpControllers
{
    /**
     * Model Constructor Pipeline.
     *
     * @var \Larawiz\Larawiz\Construction\Controller\ControllerConstructorPipeline
     */
    protected $pipeline;

    /**
     * WriteDatabaseModels constructor.
     *
     * @param  \Larawiz\Larawiz\Construction\Controller\ControllerConstructorPipeline  $pipeline
     */
    public function __construct(ControllerConstructorPipeline $pipeline)
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
        foreach ($scaffold->http->controllers as $controller) {
            $this->pipeline->send(new ControllerConstrution([
                'controller' => $controller,
            ]));
        }

        return $next($scaffold);
    }

}
