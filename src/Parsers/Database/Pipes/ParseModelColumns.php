<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;

class ParseModelColumns
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

            foreach ($scaffold->getRawModel($key, 'columns') as $name => $line) {

                // If the columns is a relation declaration, we will check if it needs columns.
                // If it doesn't, we can safely jump over it since relations are later parsed,
                // put if it needs a column for "belongsTo" and "morphs", we will reserve it.
                if ($this->columnIsRelation($line)) {
                    if ($this->relationUsesColumn($line)) {
                        $this->reserveColumnForRelation($model, $name);
                    }
                    continue;
                }

                // Since it's a normal column, we can proceed to instance the column data, np.
                $column = $this->createColumn($name, $line);

                // First, we will check if the column is a primary key. If a column has not been
                // set as primary key beforehand, we will force it into the model as a primary.
                // We can later change this is if the developer manually sets the primary key.
                if ($this->columnIsPrimary($model, $column)) {
                    $this->setPrimaryInModel($model, $column);
                }
                // When it's not a primary, we can need to check if it's a timestamps declaration.
                // If it is, then we proceed to tell the Model is using timestamps. Later we can
                // change this config if the dev explicitly states no timestamps should be used.
                elseif ($this->columnIsTimestamp($column)) {
                    $this->setTimestamps($model);
                }
                // Finally, we will check if the column is a Soft Deletes declaration. If it is,
                // we can set the model has using Soft Deletes so later we can use this data to
                // add the SoftDeletes trait and, if needed, point out the soft delete column.
                elseif ($this->columnIsSoftDelete($column)) {
                    $this->setSoftDelete($model, $column);
                }

                $model->columns->put($name, $column);
            }
        }

        return $next($scaffold);
    }

    /**
     * Checks if the Column line is a relation declaration.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function columnIsRelation(?string $line)
    {
        return Str::startsWith($line, array_keys(BaseRelation::RELATION_CLASSES));
    }

    /**
     * Checks if the relations uses a column in the local model.
     *
     * @param  string  $line
     * @return bool
     */
    protected function relationUsesColumn(string $line)
    {
        return in_array((string)Str::of($line)->before(':')->before(' '), BaseRelation::USES_COLUMN, true);
    }

    /**
     * Checks if the Column should be treated as a primary key and the Model has not set any primary key yet.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    protected function columnIsPrimary(Model $model, Column $column)
    {
        return $column->isPrimary() && $model->primary->column === null;
    }

    /**
     * Sets the Primary Key information in the Model instance.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     */
    protected function setPrimaryInModel(Model $model, Column $column)
    {
        $model->primary->using = true;
        $model->primary->column = $column;
    }

    /**
     * Parses a migration column.
     *
     * @param  string  $name
     * @param  string|array  $data
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function createColumn(string $name, $data)
    {
        return Column::fromLine($name, $data);
    }

    /**
     * Reserve the column name if needed.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @return void
     */
    protected function reserveColumnForRelation(Model $model, string $name)
    {
        $model->columns->put($name, null);
    }

    /**
     * Check if the Column is timestamps.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    protected function columnIsTimestamp(Column $column)
    {
        return $column->isTimestamps();
    }

    /**
     * Set the Model has using timestamps.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function setTimestamps(Model $model)
    {
        $model->timestamps->using = true;
    }

    /**
     * Check if the Column is a soft-deleted column
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    protected function columnIsSoftDelete(Column $column)
    {
        return $column->isSoftDeletes();
    }

    /**
     * Set the Soft Delete Column.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     */
    protected function setSoftDelete(Model $model, Column $column)
    {
        $model->softDelete->using = true;
        $model->softDelete->column = $column->name;
    }
}

