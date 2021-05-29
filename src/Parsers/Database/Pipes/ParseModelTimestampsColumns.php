<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelTimestampsColumns
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

            $timestamps = $scaffold->rawDatabase->get("models.{$key}.timestamps");

            // If there is manual timestamps data, we will use that instead of guessing
            // if the model should use timestamps or not. Otherwise, we will check if
            // there are timestamps columns declared and use the default timestamp.
            if ($timestamps) {
                $this->setCustomTimestamps($model, $timestamps);
            } else {
                $this->guessTimestamps($model);
            }
        }

        return $next($scaffold);
    }

    /**
     * Sets custom timestamps for the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  array  $timestamps
     */
    protected function setCustomTimestamps(Model $model, array $timestamps)
    {
        $model->timestamps->using = Arr::hasAny($timestamps, ['created_at', 'updated_at']);

        $model->timestamps->createdAtColumn = $this->getTimestampColumn($model,
            Arr::get($timestamps, 'created_at')
        );

        $model->timestamps->updatedAtColumn = $this->getTimestampColumn($model,
            Arr::get($timestamps, 'updated_at')
        );
    }

    /**
     * Returns the name of the timestamp column, or null if there is none set.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  null|string  $name
     * @return mixed
     */
    protected function getTimestampColumn(Model $model, ?string $name)
    {
        if (!$name) {
            return $name;
        }

        $timestamp = $model->columns->get($name);

        if (! $timestamp) {
            throw new LogicException("The [{$name}] timestamp column doesn't exists in the [{$model->key}] model.");
        }

        if (! ($timestamp->isTimestamp() && $timestamp->isNullable())) {
            throw new LogicException(
                "The [{$name}] column of [{$model->key}] must be [timestamp|timestampTz] and [nullable]."
            );
        }

        return $timestamp->name;
    }

    /**
     * Guess if the mode is using timestamps or not.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function guessTimestamps(Model $model)
    {
        if ($model->columns->has('timestamps') || $model->columns->has('timestampsTz')) {
            $model->timestamps->using = true;
        }
    }

}
