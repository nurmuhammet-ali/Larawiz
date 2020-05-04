<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Column;

class ParseModelHidden
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

            $hidden = $scaffold->rawDatabase->get("models.{$key}.hidden");

            if ($hidden === false) {
                continue;
            }

            if ($hidden === null) {
                $model->hidden->push(...$this->getHiddenColumns($model->columns));
            }

            if (is_array($hidden)) {
                $model->hidden->push(...$hidden);
            }
        }

        return $next($scaffold);
    }

    /**
     * Return a list of columns that should be hidden.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[]  $columns
     * @return array
     */
    protected function getHiddenColumns(Collection $columns)
    {
        return $columns->map(function (Column $column) {
            if ($column->shouldBeHidden()) {
                return Column::isShorthand($column->name)
                    ? Column::getShorthandDefault($column, null)
                    : $column->name;
            }
            return null;
        })->filter()->values()->all();
    }
}
