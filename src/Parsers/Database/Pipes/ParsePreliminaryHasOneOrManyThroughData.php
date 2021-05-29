<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Helpers;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Relations\HasOneOrManyThrough;
use Larawiz\Larawiz\Scaffold;

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

        return new HasOneOrManyThrough([
            'name'    => $name,
            'type'    => $methods->first()->name,
            'methods' => $methods,
            'model'   => $models->get(optional($methods->first()->arguments->first())->value),
            'through' => $models->get(optional($methods->first()->arguments->get(1))->value),
        ]);
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
}
