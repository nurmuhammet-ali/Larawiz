<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Migration;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseMigrations
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
            // We will abort if the developer already has a migration with the same name for a model.
            if (! $model->isPivot() && $scaffold->rawDatabase->has("migrations.{$model->getTableName()}")) {
                throw new LogicException(
                    "The migration already has a table named '{$model->table}' for the [$key] model."
                );
            }
        }

        foreach ($scaffold->rawDatabase->get('migrations') as $table => $columns) {
            $scaffold->database->migrations->put($table, $this->createMigration($table, $columns));
        }

        return $next($scaffold);
    }

    /**
     * Creates a Migration.
     *
     * @param  string  $table
     * @param  array  $columns
     * @return \Larawiz\Larawiz\Lexing\Database\Migration
     */
    protected function createMigration(string $table, array $columns)
    {
        $migration = new Migration([
            'table' => $table,
        ]);

        foreach ($columns as $name => $column) {
            $migration->columns->put($name, Column::fromLine($name, $column));
        }

        return $migration;
    }
}
