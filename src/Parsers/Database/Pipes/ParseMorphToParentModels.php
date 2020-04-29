<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Code\Argument;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;
use Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany;

class ParseMorphToParentModels
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
            foreach ($this->morphOneOrManyRelations($model) as $relation) {
                $this->pushModelToMorphsToRelation($model, $relation);
            }
        }

        return $next($scaffold);
    }

    /**
     * Filter the relations for Morph One Or Many.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation[]|\Illuminate\Support\Collection
     */
    protected function morphOneOrManyRelations(Model $model)
    {
        return $model->relations->filter(function (BaseRelation $relation) {
            return in_array($relation->type, ['morphOne', 'morphMany'], true);
        });
    }

    /**
     * Finds the target morphTo relation and adds the parent model to it.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany  $relation
     */
    protected function pushModelToMorphsToRelation(Model $model, MorphOneOrMany $relation)
    {
        // If the morph relation was specified, we will get the relation from the target model
        // and push the parent model into the relation. If there is not one issued, we will
        // pick the only morph relation of that model. And if it's absent, we bail out.
        $morphsRelation = $this->getMorphToRelationFromTargetModel($model, $relation);

        if ($this->relationDoesNotHaveKey($relation, $morphsRelation->name)) {
            $this->addRelationKeyToRelation($relation, $morphsRelation->name);
        }

        $morphsRelation->models->put($model->key, $model);
    }

    /**
     * Returns the target MorphTo relation instance pointed to (or guessed).
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\MorphTo
     */
    protected function getMorphToRelationFromTargetModel(Model $model, MorphOneOrMany $relation)
    {
        $morphToRelation = $this->keyOrFirstRelation($relation, $relation->model);

        if ($morphToRelation) {
            return $morphToRelation;
        }

        throw new LogicException(
            "The [{$relation->name}] of [{$model->key}] references a non-existant [morphTo] relation."
        );
    }

    /**
     * Returns the relation from the given key, or the first morphTo of the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany  $relation
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return mixed
     */
    protected function keyOrFirstRelation(MorphOneOrMany $relation, Model $model)
    {
        if ($relation->relationKey) {
            return $model->relations->get($relation->relationKey);
        }

        $relations = $model->relations->where('type', 'morphTo');

        // If the are more than one "morphTo" relation in the target model, and we don't have
        // the relation key, we will tell the developer, in a nicely manner, that he should
        // point out which of the "morphTo" relations it should use from the target model.
        if ($relations->count() > 1) {
            throw new LogicException(
                "The [{$model->key}] has more than one [morphTo] relation. Pick one for [{$relation->name}] in [{$model->key}] model."
            );
        }

        return $relations->first();
    }

    /**
     * Checks if the relation key has been set for the "morphOne" or "morphMany" relation.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany  $relation
     * @param  string  $relationKey
     * @return bool
     */
    protected function relationDoesNotHaveKey(MorphOneOrMany $relation, string $relationKey)
    {
        return ! $relation->relationKey;
    }

    /**
     * Adds the relation key to the "morphOne" or "morphMany" relation.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphOneOrMany  $relation
     * @param  string  $relationKey
     */
    protected function addRelationKeyToRelation(MorphOneOrMany $relation, string $relationKey)
    {
        $relation->relationKey = $relationKey;

        $relation->methods->first()->arguments->put(1, new Argument([
            'value' => $relationKey,
            'type' => 'string'
        ]));
    }
}
