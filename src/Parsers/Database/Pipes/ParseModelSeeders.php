<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;

class ParseModelSeeders
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
            $model->seeder = $scaffold->rawDatabase->get("models.{$key}.seeder", true);
        }

        return $next($scaffold);
    }
}
