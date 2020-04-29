<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Index;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Migration;

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
                    if (! $model->columns->has($expression)) {
                        $this->throwColumnIndexNotFound($model, $expression);
                    }

                    $index->columns->push($expression);
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
