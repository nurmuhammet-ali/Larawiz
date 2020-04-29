<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Relations\MorphTo;

class ParsePreliminaryMorphToData
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
                if ($this->isMorphTo($line)) {
                    $model->relations->put($name, $this->createRelation($name, $line));
                }
            }
        }

        return $next($scaffold);
    }

    /**
     * Return if the line is a "morphTo" relation.
     *
     * @param  null|string  $line
     * @return bool
     */
    protected function isMorphTo(?string $line)
    {
        return $line && Str::of($line)->before(':')->is('morphTo');
    }

    /**
     * Creates a "morphTo" relation.
     *
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Relations\MorphTo
     */
    protected function createRelation(string $name, string $line)
    {
        $methods = Method::parseManyMethods($line);

        $columnName = optional($methods->first()->arguments->get(0))->value ?? $name;

        // The "morphTo" relation is relatively simply. What we are gonna do later is check every
        // relation pointing to this relation and enable or disable polymorphic UUID, and if the
        // it should be nullable, or not. We must done this BEFORE all other relations are set.
        return new MorphTo([
            'name'       => $name,
            'methods'    => $methods,
            'columnName' => $columnName,
        ]);
    }
}
