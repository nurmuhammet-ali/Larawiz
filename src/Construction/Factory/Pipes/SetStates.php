<?php

namespace Larawiz\Larawiz\Construction\Factory\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Factory\FactoryConstruction;
use Larawiz\Larawiz\Lexing\Database\Model;
use Nette\PhpGenerator\ClassType;

class SetStates
{
    /**
     * Handle the factory construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Factory\FactoryConstruction  $construction
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(FactoryConstruction $construction, Closure $next)
    {
        foreach ($construction->model->factoryStates as $state) {
            $this->setState($construction->class, $construction->model, $state);
        }

        return $next($construction);
    }

    /**
     * Sets the factory state.
     *
     * @param  \Nette\PhpGenerator\ClassType  $class
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $state
     */
    protected function setState(ClassType $class, Model $model, string $state)
    {
        $class->addMethod($state)
            ->setPublic()
            ->addComment("Define the $state state.")
            ->addComment('')
            ->addComment('@return \Illuminate\Database\Eloquent\Factories\Factory')
            ->addBody(
                "\n        return \$this->state(function (array \$attributes) {" .
                "\n            return [" .
                "\n                // TODO: Add attributes for the {$model->key} \"{$state}\" state." .
                "\n            ];" .
                "\n        });"
            );
    }
}
