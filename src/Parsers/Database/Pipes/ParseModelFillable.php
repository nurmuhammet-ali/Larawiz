<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Scaffold;

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

            // If it's an arry, we will bypass everything and let the developer decide.
            if (is_array($fillable)) {
                $model->fillable->push(...$fillable);
                continue;
            }

            // If the developer issued "false", then we won't add any column to the fillable list.
            // If otherwise didn't issued anything, we will take all the columns and reject those
            // that aren't fillable. If a list is present, we will cross-check with the columns.
            if ($fillable === false) {
                continue;
            }

            // If there is no "fillable" key, or its `null` then we will go manual.
            if ($fillable === null) {
                $fillable = $this->fillable($model->columns);
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
}
