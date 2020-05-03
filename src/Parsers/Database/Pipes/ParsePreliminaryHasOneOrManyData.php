<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Helpers;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrMany;

class ParsePreliminaryHasOneOrManyData
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
            foreach ($scaffold->rawDatabase->get("models.{$key}.columns") as $name => $line) {
                if ($this->isHasOneOrMany($line)) {
                    $relation = $this->createRelation($scaffold->database->models, $model, $name, $line);

                    $relation->validateWithDefault($model);

                    $model->relations->put($name, $relation);
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Checks if the line is a "hasOne" or "hasMany" relation.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function isHasOneOrMany(?string $line)
    {
        return $line && Str::of($line)->before(' ')->before(':')->is(['hasOne', 'hasMany']);
    }

    /**
     * Creates a "hasOne" relation.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrMany
     */
    protected function createRelation(Collection $models, Model $model, string $name, string $line)
    {
        $methods = Method::parseManyMethods($this->normalizeLine($models, $model, $name, $line));

        $relation = new HasOneOrMany([
            'name'    => $name,
            'type'    => $methods->first()->name,
            'methods' => $methods,
            'model'   => $models->get($methods->first()->arguments->first()->value),
        ]);

        $this->validateRelationTargetModel($model, $relation);

        if ($this->doesntHaveLocalColumnSet($methods)) {
            $this->checkTargetModelBelongsToParentModel($model, $relation);
        }

        return $relation;
    }

    /**
     * Normalizes the belongsTo line to something we can work with.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Model[]  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return string
     */
    protected function normalizeLine(Collection $models, Model $model, string $name, string $line)
    {
        $calls = explode(' ', $line);

        // If the line is just "hasOne" or "hasMany" we will proceed to guess the model.
        if (in_array($calls[0], ['hasOne', 'hasMany'])) {
            $calls[0] .= ':' . Helpers::guessModelFromRelationName($models, $model, $name);
        }

        return implode(' ', $calls);
    }

    /**
     * Check if the target model has a "belongsTo" relation pointing to this parent model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrMany  $relation
     */
    protected function checkTargetModelBelongsToParentModel(Model $model, HasOneOrMany $relation)
    {
        $contains = $relation->model->relations->filter(function ($relation) {
            return $relation && $relation->is('belongsTo');
        })->contains('model.key', $model->key);

        if (! $contains) {
            throw new LogicException(
                "The target model [{$model->key}] for [{$relation->name}] must contains a [belongsTo] relation."
            );
        }
    }

    /**
     * Returns if the relation has not set the column to reference.
     *
     * @param  \Illuminate\Support\Collection  $methods
     * @return boolean
     */
    protected function doesntHaveLocalColumnSet(Collection $methods)
    {
        return ! optional($methods->first()->arguments->get(1))->value;
    }

    /**
     * Validates the relation has an existing target model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrMany  $relation
     */
    protected function validateRelationTargetModel(Model $model, HasOneOrMany $relation)
    {
        if ($relation->model) {
            return;
        }

        $wanted = $relation->methods->first()->arguments->first()->value;

        throw new LogicException(
            "The [{$relation->name}] relation of [{$model->key}] points to a non-existent [{$wanted}] model."
        );
    }
}
