<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;

class ParseReservationOfRelations
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
            foreach ($scaffold->rawDatabase->get("models.{$key}.columns") as $name => $line) {

                // Here we'll only reserve the relations so these can appear in order in the model and migration.
                if (Str::startsWith($line, array_keys(BaseRelation::RELATION_CLASSES))) {
                    $model->relations->put($name, null);
                }
            }
        }

        return $next($scaffold);
    }
}
