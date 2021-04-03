<?php

namespace Larawiz\Larawiz\Construction\Factory\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Factory\FactoryConstruction;
use Larawiz\Larawiz\Lexing\Database\Factory;
use Larawiz\Larawiz\Lexing\Database\Model;
use Nette\PhpGenerator\ClassType;

class SetSoftDeleteState
{
    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Factory\FactoryConstruction  $construction
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(FactoryConstruction $construction, Closure $next)
    {
        if ($construction->model->softDelete->using) {
            $this->addSoftDeletedState($construction->model, $construction->class);
        }

        return $next($construction);
    }


    /**
     * Adds a Soft Deleted state to the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Nette\PhpGenerator\ClassType  $class
     *
     * @return void
     */
    protected function addSoftDeletedState(Model $model, ClassType $class)
    {
        $class->addMethod(Factory::SOFT_DELETED_STATE)
            ->setPublic()
            ->addComment("Define the deleted state.")
            ->addComment('')
            ->addComment('@return array')
            ->addBody(
                "\nreturn \$this->state(function (array \$attributes) {" .
                "\n    return [" .
                "\n        (new {$model->class})->getDeletedAtColumn() => \$this->faker->dateTime," .
                "\n    ];" .
                "\n});"
            );
    }
}
