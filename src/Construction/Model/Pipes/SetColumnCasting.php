<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetColumnCasting
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
        $castValue = [];

        foreach ($construction->model->columns as $column) {
            if ($this->columnShouldBeCasted($column)) {
                $castValue[$column->name] = $column->castType();
            }
        }

        if (! empty($castValue)) {
            $construction->class->addProperty('casts', $castValue)
                ->addComment('The attributes that should be cast.')
                ->addComment('')
                ->addComment('@var.');
        }

        return $next($construction);
    }

    /**
     * Determines if the column should be casted in the array,
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    protected function columnShouldBeCasted(Column $column)
    {
        return $column->castType() !== 'string'
            && ! $column->relation
            && ! $column->isPrimary()
            && ! $column->isTimestamps()
            && ! $column->isSoftDeletes()
            && ! $column->shouldCastToDate();
    }
}
