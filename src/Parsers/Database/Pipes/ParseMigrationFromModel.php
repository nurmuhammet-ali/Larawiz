<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Migration;
use Larawiz\Larawiz\Scaffold;

class ParseMigrationFromModel
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
            $model->migration = new Migration([
                'table' => $model->table = $model->getTableName(),
                'columns' => $model->columns,
            ]);

            if ($model->primary->using && ! $model->primary->column->isPrimary()) {
                $model->migration->primary = $model->primary->column->getName();
                // If the column doesn't have a method called "primary", add it.
                if (! $model->primary->column->methods->contains('name', 'primary')) {
                    $model->primary->column->methods->push(Method::parseMethod('primary'));
                }
            }

            $scaffold->database->migrations->put($model->table, $model->migration);
        }

        return $next($scaffold);
    }
}
