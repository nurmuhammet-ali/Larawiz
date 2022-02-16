<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetsFactoryTrait
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
        // If the model is using factories, we will add it to the class.
        if ($construction->model->useFactory) {
            Arr::first($construction->file->getNamespaces())->addUse(HasFactory::class);
            $construction->class->addTrait(HasFactory::class);
            // Add a PHPDoc to override the Factory class for the model factory.
            $model_class = $construction->model->class;
            $construction->class->addComment(
                "@method static \Database\Factories\{$model_class}Factory factory(int|array ...\$parameters)"
            );

            $construction->class->addComment('');
        }

        return $next($construction);
    }
}
