<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;

class ParseModelFactory
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

            $factory = $scaffold->rawDatabase->get("models.{$key}.factory");

            // If the model is not using factories, we will just continue with the next.
            if (false === $model->useFactory = $factory) {
                continue;
            }

            $model->useFactory = true;

            // If the factory is a list, we will push every factory state name to the list.
            if (is_array($factory)) {
                $model->factoryStates->push(...$factory);
            }
        }

        return $next($scaffold);
    }
}
