<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Larawiz\Larawiz\Scaffold;

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

            foreach ($scaffold->rawDatabase->get("models.{$key}.hidden", []) as $column) {
                if (! $model->columns->has($column)) {
                    throw new LogicException("The hidden column [{$column}] doesn't exists in [{$key}]");
                }

                $model->hidden->push($column);
            }

//            foreach ($this->hiddenColumns($hidden, $model->columns) as $column) {
//                $this->addHiddenToModel($model, $column);
//            }
        }

        return $next($scaffold);
    }

}
