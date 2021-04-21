<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;

class ParseModelLocalScopes
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

            foreach ($scopes as $localScope) {
                if (ctype_lower($localScope[0])) {
                    $model->localScopes->push(Str::of($localScope)->ltrim('scope')->ucfirst()->start('scope'));
                }
            }
        }

        return $next($scaffold);
    }
}
