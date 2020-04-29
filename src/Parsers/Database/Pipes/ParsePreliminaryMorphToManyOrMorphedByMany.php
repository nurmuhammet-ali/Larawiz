<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany;

class ParsePreliminaryMorphToManyOrMorphedByMany
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
                if ($this->isMorphToManyOrMorphedByMany($line)) {
                    $model->relations->put(
                        $name, $this->createRelation($scaffold->database->models, $model, $name, $line)
                    );
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Checks if the line is a "morphToMany" or "morphedByMany" relation.
     *
     * @param  string  $line
     * @return bool
     */
    protected function isMorphToManyOrMorphedByMany(?string $line)
    {
        return $line && Str::of($line)->before(' ')->before(':')->is(['morphToMany', 'morphedByMany']);
    }

    /**
     * Creates a "morphToMany" or "morphedByMany" relation.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany
     */
    protected function createRelation(Collection $models, Model $model, string $name, string $line)
    {
        $methods = Method::parseManyMethods($line);

        $relation = new MorphToManyOrMorphedByMany([
            'name'    => $name,
            'type'    => $methods->first()->name,
            'methods' => $methods,
            'model'   => $models->get(optional($methods->first()->arguments->first())->value),
            'relationKey' => optional($methods->first()->arguments->get(1))->value,
        ]);

        $relation->validateWithDefault($model);

        $this->validateRelation($model, $relation);

        $this->mayGetDeclaredPivotModel($models, $relation);

        return $relation;
    }

    /**
     * Validates the relation has a model and target relation key.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     */
    protected function validateRelation(Model $model, MorphToManyOrMorphedByMany $relation)
    {
        if (! $relation->model) {
            throw new LogicException(
                "The [{$relation->name}] of [{$model->key}] needs an existing polymorphic target model."
            );
        }

        if (! $relation->relationKey) {
            throw new LogicException(
                "The [{$relation->name}] of [{$model->key}] needs an [~ble] relation key."
            );
        }
    }

    /**
     * Returns the Pivot Model if it has been issued.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     */
    protected function mayGetDeclaredPivotModel(Collection $models, MorphToManyOrMorphedByMany $relation)
    {
        // Same as the "belongsToMany" relation type, this may also use a Pivot model.
        if ($relation->methods->contains('name', 'using')) {
            $relation->using = $this->getUsingPivotModel($models, $relation);
            $relation->using->modelType = MorphPivot::class;
        }
    }

    /**
     * Returns the "using" pivot model.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphToManyOrMorphedByMany  $relation
     * @return mixed
     */
    protected function getUsingPivotModel(Collection $models, MorphToManyOrMorphedByMany $relation)
    {
        $key = $relation->methods->firstWhere('name', 'using')->arguments->first()->value;

        if ($model = $models->get($key)) {
            return $model;
        }

        throw new LogicException("The [{$relation->name}] relation is using a non-existent [{$key}] model.");
    }
}
