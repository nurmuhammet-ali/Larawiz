<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Model;

class ParseModelPrimaryKey
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
            $data = $scaffold->rawDatabase->get("models.{$key}");

            // If the Model data sets explicitly it shouldn't a use primary key we will disable it.
            // Of course, this is moot if the model already has set an incrementing key like "id",
            // because these will forcefully become primary keys even if the dev doesn't want to.
            if ($this->modelShouldNotUsePrimary($model, $data)) {
                $this->disablePrimary($model);
                continue;
            }

            // Now that we know the the developer has not disabled the primary key, we can check if
            // the model has any primary key information filled so we can guess the primary key
            // properties. We will use that information to fill the primary inside the model.
            if ($this->hasPrimaryFilled($data)) {
                $this->fillPrimary($data, $model);
                continue;
            }
        }

        return $next($scaffold);
    }

    /**
     * Returns if the model should not use any primary key.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  array  $data
     * @return bool
     */
    protected function modelShouldNotUsePrimary(Model $model, array $data)
    {
        $notUsingPrimaryKey = Arr::get($data, 'primary') === false;

        // We will bail out if the model is not using a primary key, and there is a column
        // that is an incrementing one. With stopping here we can tell the developer to
        // make up his mind because he may accidentally have filled one or the other.
        if ($notUsingPrimaryKey && optional($model->primary->column)->isPrimary()) {
            throw new LogicException("The [{$model->key}] uses a incrementing column, but primary key is [false].");
        }

        return $notUsingPrimaryKey;
    }

    /**
     * Disable the primary key for the Model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function disablePrimary(Model $model)
    {
        $model->primary->using = false;
    }

    /**
     * Checks if the Primary key has been properly filled.
     *
     * @param  array  $data
     * @return bool
     */
    protected function hasPrimaryFilled(array $data)
    {
        return Arr::has($data, 'primary');
    }

    /**
     * Fill the primary key with the data supplied.
     *
     * @param  array  $data
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function fillPrimary(array $data, Model $model)
    {
        $model->primary->using = true;

        // If there is an incrementing column, we bail out so the developer decides what to use.
        if (optional($model->primary->column)->isPrimary()) {
            throw new LogicException(
                "The [{$model->key}] already uses the primary column [{$model->primary->column->name}]."
            );
        }

        // If the developer issued just a string, we can use the string as the name of the column
        // to use as primary key. If he issued an array, we will use that information too as
        // it comes, since the developer may want to fix a non-standard column type.
        if (is_string($column = Arr::get($data, 'primary'))) {
            $model->primary->column = $model->columns->get($column);

            if (! $model->primary->column) {
                $this->throwColumnAbsentException($model, $column);
            }

        } else {
            $model->primary->column = $model->columns->get(Arr::get($data, 'column'));
            $model->primary->type = Arr::get($data, 'type');
            $model->primary->incrementing = (bool)Arr::get($data, 'incrementing');


            if (! $model->primary->column) {
                $this->throwColumnAbsentException($model, Arr::get($data, 'column'));
            }
        }


    }

    /**
     * Throw an exception if the column doesnt exists.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $column
     */
    protected function throwColumnAbsentException(Model $model, string $column)
    {
        throw new LogicException("The [{$column}] column for primary key doesn't exists in [{$model->key}] model.");
    }
}
