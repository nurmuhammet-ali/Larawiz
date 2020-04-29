<?php

namespace Larawiz\Larawiz\Writer\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Construction\Middleware\MiddlewareConstruction;
use Larawiz\Larawiz\Construction\Middleware\MiddlewareConstructorPipeline;

class WriteHttpMiddleware
{
    /**
     * Model Constructor Pipeline.
     *
     * @var \Larawiz\Larawiz\Construction\Middleware\MiddlewareConstructorPipeline
     */
    protected $pipeline;

    /**
     * WriteDatabaseModels constructor.
     *
     * @param  \Larawiz\Larawiz\Construction\Middleware\MiddlewareConstructorPipeline  $pipeline
     */
    public function __construct(MiddlewareConstructorPipeline $pipeline)
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
        foreach ($scaffold->http->middleware as $middleware) {
            $this->pipeline->send(new MiddlewareConstruction([
                'middleware' => $middleware,
            ]));
        }

        return $next($scaffold);
    }
}
