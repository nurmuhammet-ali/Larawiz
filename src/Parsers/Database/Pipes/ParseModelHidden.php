<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Config\Repository;
use Larawiz\Larawiz\Lexing\Database\Model;

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
        foreach ($scaffold->database->models as $model) {

            foreach ($this->getHiddenColumns($scaffold->rawDatabase, $model) as $key => $column) {
                if (! $model->columns->has($key)) {
                    throw new LogicException("The hidden column [{$key}] doesn't exists in [{$model->key}]");
                }

                $model->hidden->push($column);
            }
        }

        return $next($scaffold);
    }

    /**
     * Returns an array of hidden columns.
     *
     * @param  \Illuminate\Config\Repository  $database
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return array|mixed
     */
    protected function getHiddenColumns(Repository $database, Model $model)
    {
        $hidden = $database->get("models.{$model->key}.hidden", []);

        if (Arr::isAssoc($hidden)) {
            return $hidden;
        }

        return array_combine($hidden, $hidden);
    }

}
