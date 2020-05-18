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
use Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo;

class ParsePreliminaryBelongsToData
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
                if ($this->isBelongsTo($line)) {
                    $relation = $this->createRelation($scaffold->database->models, $model, $name, $line);
                    $model->relations->put($name, $relation);
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Returns if the line is a "belongsTo" relation.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function isBelongsTo(?string $line)
    {
        return $line && Str::of($line)->before(' ')->before(':')->is('belongsTo');
    }

    /**
     * Creates a "belongsTo" relation.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo
     */
    protected function createRelation(Collection $models, Model $model, string $name, string $line)
    {
        // Now we have everything sorted, we can create a new BelongsTo relation.
        $methods = Method::parseManyMethods($this->normalizeLine($models, $model, $name, $line));

        return new BelongsTo([
            'name'      => $name,
            'columnKey' => optional($methods->first()->arguments->get(1))->value,
            'methods'   => $methods,
            'model'     => $models->get($methods->first()->arguments->first()->value),
        ]);
    }

    /**
     * Normalizes the belongsTo line to something we can work with.
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

        // If the line is just "belongsTo" we will proceed to guess the model.
        if ($calls[0] === 'belongsTo') {
            $calls[0] .= ':' . Helpers::guessModelFromRelationName($models, $model, $name);
        }

        // If the line doesn't includes the column, we will guess it.
        if (! Str::contains($calls[0], ',')) {
            $calls[0] = $this->guessParentModelColumn($models, $model, $name, $calls[0]);
        }

        return implode(' ', $calls);
    }

    /**
     * Guesses the column for the parent model.
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @param  string  $line
     * @return string
     */
    protected function guessParentModelColumn(Collection $models, Model $model, string $name, string $line)
    {
        $parent = Str::between($line, ':', ',');

        // If the name of the relation is the same name of the parent model, no need to guess it.
        // Laravel automatically uses the method name as base along the primary key name of the
        // model, like "user_id". As for the migration table column we will revisit it later.
        if ($parent === Str::studly($name)) {
            return $line;
        }

        $instance = $models->get($parent);

        if (! $instance) {
            throw new LogicException(
                "The [{$name}] relation in [{$model->key}] points to non-existent [{$parent}] model."
            );
        }

        // Otherwise we will need to tell Laravel we're gonna use a non-guessable column name using
        // the primary key. For this to work, we will need first to check if the parent model has
        // a primary key, otherwise we will have to bail out since this has become unguessable.
        if (! $instance->primary->using) {
            throw new LogicException(
                "The [{$name}] relation in [{$model->key}] points needs a column name for [{$parent}] model."
            );
        }

        return Str::finish($line, ',') . $instance->singular() . '_' . $instance->primary->column->name;
    }
}
