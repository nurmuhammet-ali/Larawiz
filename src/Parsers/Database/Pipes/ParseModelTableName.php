<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelTableName
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
            $model->table = $scaffold->rawDatabase->get("models.{$key}.table");
        }

        $this->ensureNoTablesAreDuplicated($scaffold->database->models);

        return $next($scaffold);
    }

    /**
     * Ensure all models tables do not repeat themselves.
     *
     * @param  \Illuminate\Support\Collection  $models
     */
    protected function ensureNoTablesAreDuplicated(Collection $models)
    {
        $duplicates = $models->map->getTableName()->duplicates();

        if ($duplicates->isNotEmpty()) {
            $table = $duplicates->first();
            $keys = $duplicates->keys()->implode(', ');

            throw new LogicException("The table [{$table}] is duplicated in [{$keys}].");
        }
    }
}
