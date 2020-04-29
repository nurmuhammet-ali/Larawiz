<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;

class ParseModelObserver
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
            $model->observer = $scaffold->rawDatabase->get("models.{$model->key}.observer", false);
        }

        return $next($scaffold);
    }
}
