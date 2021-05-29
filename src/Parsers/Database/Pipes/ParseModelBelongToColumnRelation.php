<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelBelongToColumnRelation
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
            foreach ($model->relations->filter->is('belongsTo') as $relation) {
                $this->makeBelongsToColumn($model, $relation);
            }
        }

        return $next($scaffold);
    }

    /**
     * Creates a belongs to column for the model migration.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Column[]|\Illuminate\Support\Collection
     */
    protected function makeBelongsToColumn(Model $model, BelongsTo $relation)
    {
        // If the relation column is already set, we will get the last part of the column name since
        // it references the column name of the target model. We will clone the column, change the
        // method for a non-primary method for the Blueprint, and and use it in the column data.
        if ($relation->hasColumnKey()) {
            $column = $this->newColumnFromParentColumn($relation);
        }
        // If we don't have a column, we can still get the primary column as long is being used.
        elseif ($relation->model->primary->using) {
            $column = $this->newColumnFromPrimary($relation);
        }

        // We reserved the column name previously using the relation name, so here we will fill it.
        if (isset($column)) {
            $column->relation = $relation;
            $column->comment = "Created for [{$relation->name}] relation.";
            $column->methods = $this->adjustColumnMethods($column, $relation);

            return $model->columns->put($relation->name, $column);
        }

        // We don't have column or primary column, so we will bail out and tell the developer
        // he needs to set a column name for the relation because we can't guess it anyway.
        // The target model may have multiple columns and we can not just blindly guess.
        throw new LogicException(
            "The [{$relation->name}] relation in [{$model->key}] needs a column of [{$relation->model->key}]."
        );
    }

    /**
     * Returns the column from the target model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function newColumnFromParentColumn(BelongsTo $relation)
    {
        $key = $relation->getModelColumnFromColumnKey();

        if ($column = $relation->model->columns->get($key)) {
            $column = clone $column;

            $column->type = $this->getTypeForRelation($column);
            $column->name = $relation->columnKey;
            return $column;
        }

        // If the column name in the model doesn't exists, we will tell the developer.
        throw new LogicException(
            "The relation [{$relation->name}] references the [{$key}] column in the [{$relation->model->key}] but it doesn't exists."
        );
    }

    /**
     * Creates a new column from the Primary Column of the target relation.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function newColumnFromPrimary(BelongsTo $relation)
    {
        $name = $relation->model->singular() . '_' . Str::snake($relation->model->primary->column->name);

        return Column::fromLine($name, $this->getTypeForRelation($relation->model->primary->column));
    }

    /**
     * Returns the column type for the relation column.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return null|string
     */
    protected function getTypeForRelation(Column $column)
    {
        return $column->type === 'uuid' ? 'uuid' : $column->realMethod();
    }

    /**
     * Return the column methods to use in the migration.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo  $relation
     * @return \Larawiz\Larawiz\Lexing\Code\Method[]|\Illuminate\Support\Collection
     */
    protected function adjustColumnMethods(Column $column, BelongsTo $relation)
    {
        $string = $column->type . ':' . $column->name;

        if ($relation->isNullable()) {
            $string .= ' nullable';
        }

        if ($relation->methods->contains('name', 'index')) {
            $string .= ' index';
        } elseif ($relation->methods->contains('name', 'unique')) {
            $string .= ' unique';
        }

        return Method::parseManyMethods($string);
    }
}
