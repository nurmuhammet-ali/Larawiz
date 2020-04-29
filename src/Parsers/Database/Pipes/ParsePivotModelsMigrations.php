<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Config\Repository;
use Larawiz\Larawiz\Lexing\Database\Model;

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
        $this->mayDisablePrimaryKey($model, $database);

        // Soft Deletes in Pivot Models are not supported, se we need to disable them.
        // @see https://github.com/laravel/framework/pull/31224
        $model->softDelete->using = false;
    }

    /**
     * May disable primary key for the Pivot Model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Illuminate\Config\Repository  $database
     */
    protected function mayDisablePrimaryKey(Model $model, Repository $database)
    {
        // If the model had an ID automatically set, we will strip it here. Otherwise,
        // when the model is later constructed, the ID or UUID will be automatically
        // set as primary key if the developer issued them into the columns array.
        if ($database->get("models.{$model->key}.quick.shouldDeleteId")) {
            $model->primary->using = false;
        }
    }

}
