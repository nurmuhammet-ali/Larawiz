<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetPerPage
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
        if ($construction->model->usesNonDefaultPerPage()) {
            $construction->class->addProperty('perPage', $construction->model->perPage)
                ->setProtected()
                ->addComment('The number of models to return for pagination')
                ->addComment('')
                ->addComment('@var int');
        }

        return $next($construction);
    }
}
