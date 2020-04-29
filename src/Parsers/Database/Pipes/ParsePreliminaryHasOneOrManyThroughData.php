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
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;
use Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrManyThrough;

class ParsePreliminaryHasOneOrManyThroughData
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
                if (Str::startsWith($line, ['hasOneThrough', 'hasManyThrough'])) {
                    $model->relations->put(
                        $name, $this->createRelation($scaffold->database->models, $model, $name, $line)
                    );
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Creates a "hasOneThrough" or "hasManyThrough" relation.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrManyThrough
     */
    protected function createRelation(Collection $models, Model $model, string $name, string $line)
    {
        $methods = Method::parseManyMethods($this->normalizeLine($models, $model, $name, $line));

        $relation = new HasOneOrManyThrough([
            'name'    => $name,
            'type'    => $methods->first()->name,
            'methods' => $methods,
            'model'   => $models->get(optional($methods->first()->arguments->first())->value),
            'through' => $models->get(optional($methods->first()->arguments->get(1))->value),
        ]);

        $relation->validateWithDefault($model);

        if ($this->doesntHaveColumnsSet($methods)) {
            $this->validateTargetAndThroughModels($model, $relation);
        }

        return $relation;
    }

    /**
     * Normalizes the "hasOneThrough" or "hasManyThrough" line to something we can work with.
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

        // If the line is just "hasOneThrough" or "hasManyThrough" we will proceed to guess the models.
        if (in_array($calls[0], ['hasOneThrough', 'hasManyThrough'])) {
            $calls[0] .= ':' . Helpers::guessModelsFromRelationName($models, $model, $name);
        }

        return implode(' ', $calls);
    }

    /**
     * Validate the target and through models with the needed belonging relations.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrManyThrough  $relation
     */
    protected function validateTargetAndThroughModels(Model $model, HasOneOrManyThrough $relation)
    {
        // This are long loops but must be made. We need to check first if the models exists,
        // then we need to check if the target model belongs to the through model, and then
        // if the through model belongs to the parent model. That way the relation works.
        if (! $relation->model || ! $relation->through) {
            throw new LogicException("The [{$relation->name}] relation in [{$model->key}] has non-existent models.");
        }

        if (! $relation->model->relations->filter->is('belongsTo')->contains('model.key', $relation->through->key)) {
            $this->throwAbsentBelongsToRelation($model, $relation, $relation->through, $relation->model);
        }

        if (! $relation->through->relations->filter->is('belongsTo')->contains('model.key', $model->key)) {
            $this->throwAbsentBelongsToRelation($model, $relation, $model, $relation->through);
        }
    }

    /**
     * Throw an exception telling the belongs to relation doesn't exists.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation  $relation
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $parent
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $child
     */
    protected function throwAbsentBelongsToRelation(Model $model, BaseRelation $relation, Model $parent, Model $child)
    {
        throw new LogicException(
            "For [{$relation->name}] in [{$model->key}], the [{$child->key}] model must belong to [{$parent->key}].");
    }

    /**
     * Check if the user hasn't set columns for the relation.
     *
     * @param  \Illuminate\Support\Collection  $methods
     * @return bool
     */
    protected function doesntHaveColumnsSet(Collection $methods)
    {
        return $methods->first()->arguments->count() < 3;
    }
}
