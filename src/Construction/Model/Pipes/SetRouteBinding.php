<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetRouteBinding
{
    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->routeBinding) {
            $construction->class->addMethod('getRouteKeyName')
                ->addBody("return '{$construction->model->routeBinding}';")
                ->addComment('Get the route key for the model.')
                ->addComment('')
                ->addComment('@return string');
        }

        return $next($construction);
    }
}
