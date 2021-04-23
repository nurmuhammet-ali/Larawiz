<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelEagerLoad
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
            foreach ($scaffold->rawDatabase->get("models.{$key}.with", []) as $relation) {
                $relations = collect(explode('.', $relation));

                if ($errant = $this->recursivelyCheckModelRelation($model, $relations)) {
                    throw new LogicException(
                        "The eager load [$relation] of model [$key] contains a non valid [$errant] relation."
                    );
                }

                $model->eager->push($relation);
            }
        }

        return $next($scaffold);
    }

    /**
     * Check if the relations exists.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Illuminate\Support\Collection|string[]  $relations
     *
     * @return string|null
     */
    protected function recursivelyCheckModelRelation(Model $model, Collection $relations): ?string
    {
        if ($relations->isNotEmpty()) {
            /** @var \Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation $relation */
            if ($relation = $model->relations->get($relations->first())) {
                return $this->recursivelyCheckModelRelation($relation->model, tap($relations)->shift());
            }

            return $relations->first();
        }

        return null;
    }
}
