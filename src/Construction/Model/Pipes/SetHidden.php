<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetHidden
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
        if ($construction->model->hidden->isNotEmpty()) {
            $construction->class->addProperty('hidden', $construction->model->hidden->all())
                ->setProtected()
                ->addComment('The attributes that should be hidden for serialization.')
                ->addComment('')
                ->addComment('@var array');
        }

        return $next($construction);
    }
}
