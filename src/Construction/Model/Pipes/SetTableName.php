<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetTableName
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
        if ($construction->model->usesNonDefaultTable()) {
            $construction->class->addProperty('table', $construction->model->table)
                ->setProtected()
                ->addComment('The table associated with the model.')
                ->addComment('')
                ->addComment('@var string');
        }

        return $next($construction);
    }
}
