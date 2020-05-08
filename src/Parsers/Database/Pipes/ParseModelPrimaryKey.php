<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;

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

            // If the model has more than one auto-increment, we will tell the dev to pick
            // one. This will avoid any non-intended behaviour for scaffolding since most
            // SQL databases don't support more than one auto-incrementing column types.
            $this->shouldHaveOnlyOneAutoIncrementing($model);

            // If there is a primary key declaration we will use that to set the primary
            // key in the model. If not, we will try to guess it from the model columns
            // declared previously. If there is no primary column, we will disable it.
            if ($primary = $scaffold->rawDatabase->get("models.{$key}.primary")) {
                $this->setPrimaryKey($model, $primary);
            } else {
                $this->guessPrimaryKeyStatus($model);
            }
        }

        return $next($scaffold);
    }

    /**
     * Guesses the primary key status of the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return void
     */
    protected function guessPrimaryKeyStatus(Model $model)
    {
        // First, we are gonna check if the "id" key exists. If not, we will check for any
        // other column set as auto-incrementing. If both fails we will set the model as
        // not having any primary key at all since it may be what the developer wants.
        $primary = $model->columns->get('id') ?? $model->columns->filter()->first->isPrimary();

        if ($primary) {
            $this->setColumnAsPrimaryKey($model, $primary);
        } else {
            $this->disablePrimaryKey($model);
        }
    }

    /**
     * Sets the model Primary Key as the ID column.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $primary
     */
    protected function setColumnAsPrimaryKey(Model $model, Column $primary)
    {
        $model->primary->using = true;

        $model->primary->column = $primary;
        $model->primary->type = $primary->phpType();
        $model->primary->incrementing = $primary->isPrimary();
    }

    /**
     * Verify the model has only one auto-incrementing column.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function shouldHaveOnlyOneAutoIncrementing(Model $model)
    {
        $autoIncrementing = $model->columns->filter(function ($column) {
            return $column && $column->isPrimary();
        });

        if ($autoIncrementing->count() > 1) {
            throw new LogicException("The [{$model->key}] has more than one auto-incrementing column.");
        }
    }

    /**
     * Disable the primary key for the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function disablePrimaryKey(Model $model)
    {
        $model->primary->using = false;
    }

    /**
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param array|string $primary
     */
    protected function setPrimaryKey(Model $model, $primary)
    {
        // There are two types of primary declarations: a simple column name or a custom type.
        // Because I'm lazy, we will transform the string declaration to an array and let it
        // guess the type and auto-incrementing nature of it in case these are not issued.
        if (is_string($primary)) {
            $primary = [ 'column' => $primary ];
        }

        $this->setColumnAsPrimaryKey($model,
            $this->retrieveColumnFromPrimaryData($model, Arr::get($primary, 'column'))
        );

        // If the primary key data as keys that override the type, we will use them.
        $model->primary->incrementing = Arr::get($primary, 'incrementing', $model->primary->incrementing);
        $model->primary->type = Arr::get($primary, 'type', $model->primary->type);
    }

    /**
     * Returns the column used for primary key.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  null|string  $primary
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function retrieveColumnFromPrimaryData(Model $model, string $primary)
    {
        if ($column = $model->columns->get($primary)) {
            return $column;
        }

        throw new LogicException("The [{$primary}] primary column in [{$model->key}] doesn't exists.");
    }
}
