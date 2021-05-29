<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\MorphTo;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelMorphToColumnRelation
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
        foreach ($scaffold->database->models as $model) {
            foreach ($model->relations->where('type', 'morphTo') as $relation) {
                $this->createMorphToColumn($model, $relation);
            }
        }

        return $next($scaffold);
    }

    /**
     * Creates a column for the Morph To.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphTo  $relation
     * @return \Larawiz\Larawiz\Lexing\Database\Column[]|\Illuminate\Support\Collection
     */
    protected function createMorphToColumn(Model $model, MorphTo $relation)
    {
        // The morphs should have by this now all related parent models. We can cross check every
        // of them to see if they're all using "id" or "uuid". If one of them doesn't comply, we
        // will just bail out since using polymorphic requires all models to abide to the same.
        $methods = $relation->migrationMethods(
            $this->shouldUseUuid($relation->models, $model, $relation)
        );

        // There is no need to worry for this column. Later, we will cross-check the "morphOne",
        // "morphMany" and "morphToMany" relations from other models and set the column as
        // "uuidMorphs" or just "morphs". If we found different target models, we will bail.
        $column = Column::fromLine($relation->columnName, $methods);

        $column->relation = $relation;

        return $model->columns->put($relation->name, $column);
    }

    /**
     * Checks if the parent models of the morph relation use all UUID or ID.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\MorphTo  $relation
     * @return bool
     */
    protected function shouldUseUuid(Collection $models, Model $model, MorphTo $relation)
    {
        if ($models->every->hasAutoIncrementPrimaryKey()) {
            return false;
        }

        if ($models->every->hasUuidPrimaryKey()) {
            return true;
        }

        throw new LogicException(
            "Models pointing to [{$relation->name}] in [{$model->key}] must ALL use [uuid] or [id]."
        );
    }
}
