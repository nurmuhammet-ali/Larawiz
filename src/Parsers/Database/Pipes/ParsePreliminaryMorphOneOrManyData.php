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
use Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany;

class ParsePreliminaryMorphOneOrManyData
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
                if ($this->isMorphOneOrMany($line)) {
                    $model->relations->put($name,
                        $this->createRelation($scaffold->database->models, $model, $name, $line)
                    );
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Returns if the line is a "morphOne" or "morphMany" relation.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function isMorphOneOrMany(?string $line)
    {
        return $line && Str::of($line)->before(' ')->before(':')->is(['morphOne', 'morphMany']);
    }

    /**
     * Creates a "morphOne" or "morphMany" relation.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany
     */
    protected function createRelation(Collection $models, Model $model, string $name, string $line)
    {
        $methods = Method::parseManyMethods($this->normalizeLine($models, $model, $name, $line));

        $relation = new MorphOneOrMany([
            'name'        => $name,
            'type'        => $methods->first()->name,
            'methods'     => $methods,
            'model'       => $models->get($methods->first()->arguments->first()->value),
            'relationKey' => $methods->first()->arguments->get(1)->value,
        ]);

        if (! $relation->model) {
            $this->throwModelDoesntExists($model, $name);
        }

        $relation->validateWithDefault($model);

        // Now that our relation is created, we will smartly put the parent model into the "morphTo"
        // relation. This way we can later detect if the migration should be using polymorphic UUID
        // and nullable columns, which saves us multiple loops in model relations checking for it.
        $this->injectParentModelInTargetRelation($model, $relation);

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

        // If the line is just "morphOne" or "morphMany" we will proceed to guess the model.
        if (in_array($calls[0], ['morphOne', 'morphMany'])) {
            $calls[0] .= ':' . Helpers::guessModelFromRelationName($models, $model, $name);
        }

        // If the polymorphic relation doesn't includes the name of the polymorphic target
        // relation, we will check the target model and check for a polymorphic relation
        // to point to. If it has more than one we will bail out so the dev picks one.
        if (! Str::contains($calls[0], ',')) {
            $calls[0] = $this->getTargetPolymorphicRelation($models, $model, $name, $calls[0]);
        }

        return implode(' ', $calls);
    }

    /**
     * Return the name of the target polymorphic relation.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Model[]  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return string
     */
    protected function getTargetPolymorphicRelation(Collection $models, Model $model, string $name, string $line)
    {
        if (! $childModel = $models->get(Str::between($line, ':', ','))) {
            $this->throwModelDoesntExists($model, $name);
        }

        $relations = $childModel->relations->where('type', 'morphTo');

        if ($relations->count() > 1) {
            throw new LogicException("The [{$childModel->key}] has multiple [morphTo] relations, you need to pick one.");
        }

        if ($relations->isEmpty()) {
            throw new LogicException("The [{$childModel->key}] doesn't have a [morphTo] relation.");
        }

        return Str::finish($line, ',') . $relations->first()->name;
    }

    /**
     * Throws an exception if the target model doesn't exist.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     */
    protected function throwModelDoesntExists(Model $model, string $name)
    {
        throw new LogicException("The [{$name}] relation of [{$model->key}] must have a target model.");
    }

    /**
     * Injects the parent model into the target "morphTo" relation.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany  $relation
     */
    protected function injectParentModelInTargetRelation(Model $model, MorphOneOrMany $relation)
    {
        if (! $targetRelation = $relation->model->relations->get($relation->relationKey)) {
            throw new LogicException(
                "The [{$relation->name}] points to a polymorphic relation [{$relation->relationKey}] that doesn't exists in [{$relation->model->key}]."
            );
        }

        $targetRelation->models->put($model->key, $model);
    }
}
