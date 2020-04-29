<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Migration;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;
use Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany;

class ParseMorphToOrByManyAutomaticPivot
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
            foreach ($this->morphsRelationsWithoutPivotModel($model) as $relation) {
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
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany[]|\Illuminate\Support\Collection
     */
    protected function morphsRelationsWithoutPivotModel(Model $model)
    {
        return $model->relations->filter(function (BaseRelation $relation) {
            return $relation->is(['morphToMany', 'morphedByMany'])
                && ! $relation->isUsingPivotModel();
        });
    }

    /**
     * Creates a "BelongsToMany" automatic pivot migration.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Migration
     */
    protected function createPivotTable(Scaffold $scaffold, Model $model, MorphToManyOrMorphedByMany $relation)
    {
        // Before doing anything, we will guess the table name from the relation data and check
        // if the migration exists or not. If that's the case, the developer has already made
        // the migration, or a preceding relation made the same migration table previously.
        if ($this->migrationExists($scaffold, $tableName = $this->tableName($relation))) {
            return null;
        }

        $this->checkPrimaryKeyUniformity($scaffold, $relation);

        $migration = new Migration([
            'table'            => $tableName,
            'comment'          => "Polymorphic Pivot created automatically for [{$relation->relationKey}].",
            'fromGuessedPivot' => true,
        ]);

        // We need to swap the models since "morphToMany" is for parents, and "morphedByMany" for children.
        if ($relation->is('morphToMany')) {
            $migration->columns->push($this->makeBelongingColumnForChildModel($relation->model));
            $migration->columns->push($this->makeMorphColumnForParentModels($relation, $model));
        } else {
            $migration->columns->push($this->makeBelongingColumnForChildModel($model));
            $migration->columns->push($this->makeMorphColumnForParentModels($relation, $relation->model));
        }

        $migration->columns = $migration->columns->keyBy('name');


        return $migration;
    }

    /**
     * Return the pivot table name.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     * @return string
     */
    protected function tableName(MorphToManyOrMorphedByMany $relation)
    {
        return Str::plural($relation->relationKey);
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
     * Returns if the automatic pivot relation should use auto-incrementing ID or UUID.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     * @return string
     */
    protected function checkPrimaryKeyUniformity(Scaffold $scaffold, MorphToManyOrMorphedByMany $relation)
    {
        $parentModels = $this->getParentModels($scaffold, $relation);

        if ($parentModels->every->hasAutoIncrementPrimaryKey()) {
            return true;
        }

        if ($parentModels->every->hasUuidPrimaryKey()) {
            return false;
        }

        throw new LogicException(
            "The polymorphic relation [{$relation->name}] must have all parent models with same primary key type."
        );
    }

    /**
     * Return all the parent models pointing to the same relation key.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Model[]|\Illuminate\Support\Collection
     */
    protected function getParentModels(Scaffold $scaffold, MorphToManyOrMorphedByMany $relation)
    {
        // Since both polymorphic many-to-many relations are optional, we need to make two loops.
        // One collects the "morphToMany" parent models, and the second collects the target
        // parent models of "morphedByMany" relations. Then, we merge both collections.
        $parents = $scaffold->database->models->filter(function (Model $model) use ($relation) {
            return $model->relations->contains->isPolymorphicParentOfWithoutPivot($relation->relationKey);
        });

        $childParents = $scaffold->database->models->mapWithKeys(function (Model $model) use ($relation) {
            return $model->relations->map(function (BaseRelation $baseRelation) use ($relation) {
                if ($baseRelation->isPolymorphicChildOfWithoutPivot($relation->relationKey)) {
                    return $baseRelation->model;
                }
                return null;
            })->filter();
        })->flatten(1)->unique('key');

        return $parents->merge($childParents)->unique('key');
    }

    /**
     * Create the parent column for the pivot table.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function makeBelongingColumnForChildModel(Model $model)
    {
        return Column::fromLine(
            $model->singular() . '_' . $model->primary->column->getName(),
            $model->primary->column->realMethod()
        );
    }

    /**
     * Create the child column for the relation.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function makeMorphColumnForParentModels(MorphToManyOrMorphedByMany $relation, Model $model)
    {
        return Column::fromLine(
            $relation->relationKey, $model->hasAutoIncrementPrimaryKey() ? 'morphs' : 'uuidMorphs'
        );
    }
}
