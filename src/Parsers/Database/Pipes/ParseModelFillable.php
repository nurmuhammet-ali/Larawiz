<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Support\Collection;

class ParseModelFillable
{
    /**
     * Handle the parsing of the Database scaffold.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        foreach ($scaffold->database->models as $key => $model) {

            $fillable = $scaffold->rawDatabase->get("models.{$key}.fillable");

            // If the developer issued "false", then we won't add any column to the fillable list.
            // If otherwise didn't issued anything, we will take all the columns and reject those
            // that aren't fillable. If a list is present, we will cross-check with the columns.
            if ($fillable === false) {
                continue;
            }

            if ($fillable === null) {
                $fillable = $this->fillable($model->columns);
            }

            if (is_array($fillable)) {
                $fillable = $this->fillable($this->intersectFillable($model->columns, $fillable));
            }

            $model->fillable->push(...$fillable);
        }

        return $next($scaffold);
    }

    /**
     * Returns all the fillable columns for a given list of columns.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[]  $columns
     * @return \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[]
     */
    protected function fillable(Collection $columns)
    {
        // We will get the array of columns and get those except that are should
        // not be filled. Additionally, we will also include in the filter the
        // array of columns that are a relation which shouldn't be fillable.
        return $columns->reject->isUnfillable()->keys();
    }

    /**
     * Checks if the fillable columns issued manually exists in the model.
     *
     * @param  \Illuminate\Support\Collection  $columns
     * @param  array  $fillable
     * @return \Illuminate\Support\Collection
     */
    protected function intersectFillable(Collection $columns, array $fillable)
    {
        $difference = collect(array_flip($fillable))->diffKeys($columns);

        if ($difference->count()) {

            $difference = $difference->keys()->map(function ($column) {
                return "[{$column}]";
            })->implode(', ');

            throw new LogicException("The fillable columns contains non-existant columns: {$difference}.");
        }

        return $difference;
    }
}
