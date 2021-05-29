<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelRouteBinding
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
            $model->routeBinding = $scaffold->rawDatabase->get("models.{$key}.route");

            if ($model->routeBinding && ! $model->columns->has($model->routeBinding)) {
                throw new LogicException(
                    "The route binding [$model->routeBinding] column for the [{$key}] model doesn't exists."
                );
            }

        }

        return $next($scaffold);
    }
}
