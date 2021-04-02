<?php

namespace Larawiz\Larawiz\Construction\Factory\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Construction\Factory\FactoryConstruction;
use Larawiz\Larawiz\Lexing\Database\Model;

class SetConfigure
{
    /**
     * Handle the factory construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Factory\FactoryConstruction  $construction
     * @param  \Closure  $next
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function handle(FactoryConstruction $construction, Closure $next)
    {
        $construction->class->addMethod('configure')
            ->setPublic()
            ->addComment("Configure the model factory")
            ->addComment('')
            ->addComment('@return $this')
            ->setBody($this->setConfigureBody($construction->model));

        return $next($construction);
    }

    protected function setConfigureBody(Model $model): string
    {
        $modelArguments = $model->class . ' $' . Str::camel($model->class);

        return
            "\n        return \$this->afterMaking(function ($modelArguments) {" .
            "\n            // TODO: Add after making configuration." .
            "\n        })->afterCreating($modelArguments) {" .
            "\n            // TODO: Add after creating configuration." .
            "\n        });";
    }
}
