<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Helpers;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParsePreliminaryBelongsToManyData
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
                if ($this->isBelongsToMany($line)) {
                    $model->relations->put(
                        $name, $this->createRelation($scaffold->database->models, $model, $name, $line)
                    );
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Checks if the line is a "belongsToMany" relation.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function isBelongsToMany(?string $line)
    {
        return $line && Str::of($line)->before(' ')->before(':')->is('belongsToMany');
    }

    /**
     * Creates a "belongsToMany" relation.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany
     */
    protected function createRelation(Collection $models, Model $model, string $name, string $line)
    {
        $methods = Method::parseManyMethods($this->normalizeLine($models, $model, $name, $line));

        $relation = new BelongsToMany([
            'name'    => $name,
            'type'    => 'belongsToMany',
            'methods' => $methods,
            'model'   => $models->get($methods->first()->arguments->first()->value),
        ]);

        $relation->validateWithDefault($model);

        $this->mayGetDeclaredPivotModel($models, $relation);

        return $relation;
    }

    /**
     * Normalizes the "belongsToMany" line to something we can work with.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return string
     */
    protected function normalizeLine(Collection $models, Model $model, string $name, string $line)
    {
        $calls = explode(' ', $line);

        // If the line is just "belongsToMany" we will proceed to guess the model.
        if ($calls[0] === 'belongsToMany') {
            $calls[0] .= ':' . Helpers::guessModelFromRelationName($models, $model, $name);
        }

        return implode(' ', $calls);
    }

    /**
     * Returns the Pivot Model if it has been issued.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     */
    protected function mayGetDeclaredPivotModel(Collection $models, BelongsToMany $relation)
    {
        // Since the BelongsToMany may have a pivot model, we will check if it's using one.
        // In that case, we will include into the model and forcefully change its type to
        // a Pivot class to avoid errors when using the relation in the scaffolded app.
        if ($relation->methods->contains('name', 'using')) {
            $relation->using = $this->getUsingPivotModel($models, $relation);
            $relation->using->modelType = Pivot::class;
        }
    }

    /**
     * Returns the "using" pivot model.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Relations\BelongsToMany  $relation
     * @return mixed
     */
    protected function getUsingPivotModel(Collection $models, BelongsToMany $relation)
    {
        $key = $relation->methods->firstWhere('name', 'using')->arguments->first()->value;

        if ($model = $models->get($key)) {
            return $model;
        }

        throw new LogicException("The [{$relation->name}] relation is using a non-existent [{$key}] model.");
    }
}
