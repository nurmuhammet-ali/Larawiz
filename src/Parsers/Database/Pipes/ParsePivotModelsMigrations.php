<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Config\Repository;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Lexing\Code\Argument;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;
use Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany;
use Larawiz\Larawiz\Scaffold;

class ParsePivotModelsMigrations
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
        foreach ($scaffold->database->models->filter->isPivot() as $model) {
            $this->cleanPivotModel($model, $scaffold->rawDatabase);

            if ($model->modelType === Pivot::class) {
                $this->mayCorrectTableName($model, $scaffold->database->models);
            }
        }

        return $next($scaffold);
    }

    /**
     * Clean the Pivot model from unwanted primary and timestamps.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Illuminate\Config\Repository  $database
     */
    protected function cleanPivotModel(Model $model, Repository $database)
    {
        // If the model had an ID automatically set, we will strip it here. Otherwise,
        // when the model is later constructed, the ID or UUID will be automatically
        // set as primary key if the developer issued them into the columns array.
        if ($database->get("models.{$model->key}.quick.shouldDeleteId")) {
            $model->primary->using = false;
        }

        // Soft Deletes in Pivot Models are not supported, se we need to disable them.
        // @see https://github.com/laravel/framework/pull/31224
        $model->softDelete->using = false;
    }


    /**
     * Adds the Pivot Model table name to the relation methods.
     *
     * @param \Larawiz\Larawiz\Lexing\Database\Model  $pivot
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Model  $models
     */
    protected function mayCorrectTableName(Model $pivot, Collection $models)
    {
        // We will cycle through each model with a "belongsToMany" relation that is using this
        // Pivot model, and forcefully inject the Pivot table name to the relation methods.
        // We have to do it because Laravel doesn't uses the Pivot automatic table name.
        $models->each(function (Model $model) use ($pivot) {
            $model->relations->each(function ($relation) use ($model, $pivot) {
                if ($this->usesPivotModel($relation, $pivot) &&
                    $this->pivotDoesntFollowsNamingConvention($model, $relation->model, $pivot)) {

                    $this->setTableNameInPivot($pivot);

                    if ($this->hasNoTableName($relation)) {
                        $this->addPivotTableName($relation->methods, $pivot);
                    }
                }
            });
        });
    }

    /**
     * Check if the relation uses the Model has a pivot and doesn't uses a custom table.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation  $relation
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $pivot
     * @return bool
     */
    protected function usesPivotModel(BaseRelation $relation, Model $pivot)
    {
        return $relation instanceof BelongsToMany
            && $relation->isUsingPivotModel()
            && $relation->using->key === $pivot->key;
    }

    /**
     * Returns if the second argument of the relation is empty.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     * @return bool
     */
    protected function hasNoTableName(BelongsToMany $relation)
    {
        return ! $relation->methods->first()->arguments->get(1);
    }

    /**
     * Check if the pivot uses "singular_singular" naming convention.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $parent
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $child
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $pivot
     * @return bool
     */
    protected function pivotDoesntFollowsNamingConvention(Model $parent, Model $child, Model $pivot)
    {
        // Let's say we have "UserRole". The correct table name for this is "role_user" so we
        // will get the class name singular-snaked automatically and compare that with what
        // Eloquent expects, which is the sorted model names together with an underscore.
        $expected = collect([$parent, $child])->map->singular()->sort()->implode('_');

        $received = Str::of($pivot->class)->singular()->snake();

        return ! $received->is($expected);
    }

    /**
     * Takes the table name of the model and injects it as second parameter to the
     * "belongsToMany" method call.
     *
     * @param  \Illuminate\Support\Collection  $methods
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $pivot
     */
    protected function addPivotTableName(Collection $methods, Model $pivot)
    {
        $methods->first()->arguments->put(1, new Argument([
            'value' => $pivot->getTableName(),
            'type' => 'string'
        ]));
    }

    /**
     * Sets the plural name as table name in the pivot.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $pivot
     */
    protected function setTableNameInPivot(Model $pivot)
    {
        $pivot->table = $pivot->table ?? $pivot->getPluralTableName();
    }

}
