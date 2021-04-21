<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Lexing\Database\GlobalScope;
use Larawiz\Larawiz\Scaffold;

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
            $scopes = $scaffold->rawDatabase->get("models.{$key}.scopes", []);

            foreach ($scopes as $globalScope) {
                if (ctype_upper($globalScope[0])) {
                    $model->globalScopes->push(Str::finish($globalScope, 'Scope'));
                }
            }
        }

        return $next($scaffold);
    }
}
