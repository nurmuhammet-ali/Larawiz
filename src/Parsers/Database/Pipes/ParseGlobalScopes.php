<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\GlobalScope;

class ParseGlobalScopes
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
            foreach ($scaffold->rawDatabase->get("models.{$key}.scopes", []) as $scope) {
                $model->globalScopes->push(Str::finish($scope, 'Scope'));
            }
        }

        return $next($scaffold);
    }
}
