<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Nette\PhpGenerator\ClassType;
use Larawiz\Larawiz\Lexing\Database\Timestamps;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetTimestamp
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
        if ($construction->model->timestamps->using) {
            $this->setTimestamps($construction->model->timestamps, $construction->class);
        } else {
            $construction->class->addProperty('timestamps', false)
                ->setPublic()
                ->addComment('Indicates if the model should be timestamped.')
                ->addComment('')
                ->addComment('@var bool');
        }

        return $next($construction);
    }

    /**
     * Set the timestamps for the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Timestamps  $timestamps
     * @param  \Nette\PhpGenerator\ClassType  $class
     */
    protected function setTimestamps(Timestamps $timestamps, ClassType $class)
    {
        if (! $timestamps->usingDefaultCreatedAt()) {
            $class->addConstant('CREATED_AT', $timestamps->createdAtColumn)
                ->setPublic()
                ->addComment('The "created at" column name.')
                ->addComment('')
                ->addComment('@var ' . ($timestamps->createdAtColumn ? 'string' : 'null'));
        }

        if (! $timestamps->usingDefaultUpdatedAt()) {
            $class->addConstant('UPDATED_AT', $timestamps->updatedAtColumn)
                ->setPublic()
                ->addComment('The "updated at" column name.')
                ->addComment('')
                ->addComment('@var ' . ($timestamps->updatedAtColumn ? 'string' : 'null'));
        }

        if ($timestamps->usingCreatedAt()) {
            $class->addComment(
                '@property-read \Illuminate\Support\Carbon $' . $timestamps->createdAtColumn
            );
        }

        if ($timestamps->usingUpdatedAt()) {
            $class->addComment(
                '@property-read \Illuminate\Support\Carbon $' . $timestamps->updatedAtColumn
            );
        }
    }
}
