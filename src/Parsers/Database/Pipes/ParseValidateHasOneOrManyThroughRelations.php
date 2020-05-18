<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;
use Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrManyThrough;

class ParseValidateHasOneOrManyThroughRelations
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
            foreach($model->relations->filter->is(['hasOneThrough', 'hasManyThrough']) as $relation) {
                $relation->validateWithDefault($model);

                if ($this->doesntHaveColumnsSet($relation->methods)) {
                    $this->validateTargetAndThroughModels($model, $relation);
                }
            }
        }

        return $next($scaffold);
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

        // Check if the target model belongs to the through model.
        if (! $relation->model->relations->filter()
            ->filter->is('belongsTo')
            ->contains('model.key', $relation->through->key)) {
            $this->throwAbsentBelongsToRelation($model, $relation, $relation->through, $relation->model);
        }

        // Check if the through model belongs to the parent model.
        if (! $relation->through->relations->filter()->filter->is('belongsTo')->contains('model.key', $model->key)) {
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
