<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetFillable
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
        if ($construction->model->fillable->isNotEmpty()) {
            $construction->class->addProperty('fillable', $construction->model->fillable->all())
                ->setProtected()
                ->addComment('The attributes that are mass assignable.')
                ->addComment('')
                ->addComment('@var array');
        }

        return $next($construction);
    }
}
