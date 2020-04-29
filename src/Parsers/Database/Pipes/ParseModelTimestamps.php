<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;

class ParseModelTimestamps
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
            if ($timestamps = $scaffold->rawDatabase->get("models.{$key}.timestamps")) {

                $model->timestamps->updatedAtColumn = $this->validateColumn(
                    $model, Arr::get($timestamps, 'updated_at')
                );

                $model->timestamps->createdAtColumn = $this->validateColumn(
                    $model, Arr::get($timestamps, 'created_at')
                );

                $model->timestamps->using = $model->timestamps->updatedAtColumn || $model->timestamps->createdAtColumn;
            }
        }

        return $next($scaffold);
    }

    /**
     * Validate the Column name
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  null  $name
     * @return null
     */
    protected function validateColumn(Model $model, $name = null)
    {
        if (! $name) {
            return null;
        }

        $this->exceptionOnInvalidColumn($model, $name, $model->columns->get($name));

        return $name;
    }

    /**
     * Throw an exception if the column doesn't exists or is not a timestamp.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  null|\Larawiz\Larawiz\Lexing\Database\Column  $column
     */
    protected function exceptionOnInvalidColumn(Model $model, string $name, ?Column $column)
    {
        if (! $column) {
            throw new LogicException("The timestamp-able [{$name}] column doesnt exists in the [{$model->key}].");
        }

        if (! $column->isNullable || ! $column->isTimestamp()) {
            throw new LogicException(
                "The timestamp-able [{$name}] column should be [timestamp|timestampTz] and [nullable]."
            );
        }
    }
}
