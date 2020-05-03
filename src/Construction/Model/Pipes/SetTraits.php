<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetTraits
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
        if ($construction->model->quickTraits->isNotEmpty()) {
            foreach ($construction->model->quickTraits as $trait) {

                Arr::first($construction->file->getNamespaces())->addUse($trait->fullNamespace());

                $construction->class->addTrait($trait->fullNamespace());
            }
        }

        return $next($construction);
    }
}
