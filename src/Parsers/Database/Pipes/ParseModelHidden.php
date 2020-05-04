<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
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
        foreach ($scaffold->database->models as $model) {

            foreach ($scaffold->database->get("models.{$model->key}.hidden", []) as $column) {
                $model->hidden->push($column);
            }
        }

        return $next($scaffold);
    }
}
