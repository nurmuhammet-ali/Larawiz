<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Lexing\Database\Index;
use Larawiz\Larawiz\Lexing\Database\Migration;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelIndexesForMigration
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
            if ($declaration = $scaffold->rawDatabase->get("models.{$key}.indexes")) {
                $this->setIndexes($model, $model->migration, $declaration);
            }
        }

        return $next($scaffold);
    }

    /**
     * Sets indexes from each string of the array.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @param  array  $declaration
     */
    protected function setIndexes(Model $model, Migration $migration, array $declaration)
    {
        foreach ($declaration as $line) {

            $index = new Index;

            foreach (explode(' ', $line) as $expression) {
                if ($expression === 'unique') {
                    $index->unique = true;
                } elseif (Str::startsWith($expression, 'name:')) {
                    $index->name = Str::afterLast($expression, ':');
                } else {
                    /** @var \Larawiz\Larawiz\Lexing\Database\Column $column */
                    if (! $column = $model->columns->get($expression)) {
                        $this->throwColumnIndexNotFound($model, $expression);
                    }

                    // If the column is a relation column, get the column real name.
                    $index->columns->push($column->getName());
                }
            }

            $migration->indexes->push($index);
        }
    }

    /**
     * Throws an exception if the column to make an index was not found.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $column
     */
    protected function throwColumnIndexNotFound(Model $model, string $column)
    {
        throw new LogicException("The [{$column}] doesn't exists in the [{$model->key}] to make an index.");
    }
}
