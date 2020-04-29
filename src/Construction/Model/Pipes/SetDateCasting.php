<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Timestamps;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetDateCasting
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
        $dateCastValue = $this->dateCastableColumns($construction->model)->map->name->values()->all();

        if (! empty($dateCastValue)) {
            $construction->class->addProperty('dates', $dateCastValue)
                ->addComment('The attributes that should be mutated to dates.')
                ->addComment('')
                ->addComment('@var array');
        }

        return $next($construction);
    }

    /**
     * Returns a collection of all date-castable columns.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Column[]|\Illuminate\Support\Collection
     */
    protected function dateCastableColumns(Model $model)
    {
        return $model->columns->filter(function (Column $column) use ($model) {
            return $this->columnShouldBeCastedToDate($model->timestamps, $column);
        });
    }

    /**
     * Check if the column should be caste to dates.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Timestamps  $timestamps
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    protected function columnShouldBeCastedToDate(Timestamps $timestamps, Column $column)
    {
        return $column->shouldCastToDate()
            && ! $column->isTimestamp()
            && $timestamps->notTimestamps($column->name);
    }
}
