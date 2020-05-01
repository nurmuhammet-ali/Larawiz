<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Migration;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;
use Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany;

class ParseBelongsToManyAutomaticPivot
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
        // We will cycle through any "BelongsToMany" that hasn't declared using a Pivot Model
        // (because these will be added later). If the migration exists in the raw data, we
        // will bypass the automatic pivot migration and use the developer data instead.
        foreach ($scaffold->database->models as $model) {
            foreach ($this->belongsToManyRelationsWithoutPivotModel($model) as $relation) {
                if ($migration = $this->createPivotTable($scaffold, $model, $relation)) {
                    $scaffold->database->migrations->put($migration->table, $migration);
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Return all relations that need a pivot table and not using a Pivot Model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany[]|\Illuminate\Support\Collection
     */
    protected function belongsToManyRelationsWithoutPivotModel(Model $model)
    {
        return $model->relations->filter(function (BaseRelation $relation) {
            return $relation->is('belongsToMany') && ! $relation->isUsingPivotModel();
        });
    }

    /**
     * Creates a "BelongsToMany" automatic pivot migration.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Migration
     */
    protected function createPivotTable(Scaffold $scaffold, Model $model, BelongsToMany $relation)
    {
        // Before doing anything, we will guess the table name from the relation data and check
        // if the migration exists or not. If that's the case, the developer has already made
        // the migration, or a preceding relation made the same migration table previously.
        if ($this->migrationExists($scaffold, $tableName = $this->tableName($model, $relation))) {
            return null;
        }

        $migration = new Migration([
            'table'            => $tableName,
            'fromGuessedPivot' => true,
        ]);

        $this->addColumnsToMigration($migration, $model, $relation);

        $models = Arr::sort([$model->key, $relation->model->key]);
        $migration->comment = "Pivot created automatically for [{$models[0]}] and [{$models[1]}].";

        return $migration;
    }

    /**
     * Return the pivot table name.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     * @return string
     */
    protected function tableName(Model $model, BelongsToMany $relation)
    {
        if (! $relation->model) {
            throw new LogicException("The [{$relation->name}] relation of [{$model->key}] must have a target model.");
        }

        $array = [$model->singular(), $relation->model->singular()];

        sort($array);

        return implode('_', $array);
    }

    /**
     * Checks if the table was not issued as a migration nor already created previously.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  string  $tableName
     * @return bool
     */
    protected function migrationExists(Scaffold $scaffold, string $tableName)
    {
        return $scaffold->database->migrations->has($tableName)
            || $scaffold->rawDatabase->has("migrations.{$tableName}");
    }

    /**
     * Adds both model columns to the migration.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     */
    protected function addColumnsToMigration(Migration $migration, Model $model, BelongsToMany $relation)
    {
        $column = $this->makePivotColumn($relation, $relation->model);
        $migration->columns->put($column->name, $column);

        $column = $this->makePivotColumn($relation, $model);
        $migration->columns->put($column->name, $column);

        $migration->columns = $migration->columns->sortBy('name');
    }

    /**
     * Creates a belonging column for a pivot model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function makePivotColumn(BelongsToMany $relation, Model $model)
    {
        if (! $model->primary->using) {
            throw new LogicException("The [{$model->key}] of [{$relation->name}] must have primary keys enabled.");
        }

        return Column::fromLine(
            $model->singular() . '_' . $model->primary->column->getName(),
            $model->primary->column->realMethod()
        );
    }

}
