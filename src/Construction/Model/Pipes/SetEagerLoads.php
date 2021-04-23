<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetEagerLoads
{
    /**
     * Handle the model construction
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->eager->isNotEmpty()) {
            $construction->class->addProperty('with', $construction->model->eager->toArray())
                ->setProtected()
                ->addComment('The relations to eager load on every query.')
                ->addComment('')
                ->addComment('@var array');
        }

        return $next($construction);
    }
}
