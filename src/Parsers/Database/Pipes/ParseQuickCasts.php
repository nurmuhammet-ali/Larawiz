<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Helpers;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\QuickCast;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseQuickCasts
{
    /**
     * The application namespace.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $namespace;

    /**
     * The application path.
     *
     * @var string
     */
    protected $path;

    /**
     * ParseQuickTraits constructor.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->namespace = trim($app->getNamespace(), '\\');
        $this->path = $app->path();
    }

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
            if (is_array($traits = $scaffold->rawDatabase->get("models.{$key}.casts"))) {
                $this->addCastsToModel($model, $traits);
            }
        }

        return $next($scaffold);
    }

    /**
     * Add the casts to the model
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  array  $casts
     */
    protected function addCastsToModel(Model $model, array $casts)
    {
        foreach ($casts as $column => $cast) {
            $model->quickCasts->put($column, $this->createCast($column, $cast));
        }
    }

    /**
     * Creates a Quick Cast.
     *
     * @param  string  $column
     * @param  string  $cast
     *
     * @return \Larawiz\Larawiz\Lexing\Database\QuickCast
     */
    protected function createCast(string $column, string $cast): QuickCast
    {
        $instance = new QuickCast();

        [$instance->namespace, $instance->class] = Helpers::namespaceAndClass($cast, $this->namespace . '\\' . 'Casts');

        $instance->column = $column;

        // If the cast already exists, we will create a cast reference and mark it as
        // "external". Otherwise, we will get the application base namespace and use
        // that as the base to create the cast that will be included in the model.
        if ($instance->internal = $this->castDoesntExists($cast)) {
            $instance->path = Helpers::pathFromNamespace($instance->fullNamespace(), $this->path, $this->namespace);
        }

        return $instance;
    }


    /**
     * Check if the cast doesnt exists.
     *
     * @param  string  $cast
     *
     * @return bool
     */
    protected function castDoesntExists(string $cast): bool
    {
        $castDoesntExists = ! class_exists($cast);

        // We will bail out if the cast is a trait or interface.
        if ($castDoesntExists && (trait_exists($cast) || interface_exists($cast))) {
            throw new LogicException("The [{$cast}] exists but is not a class, but a trait or interface.");
        }

        return $castDoesntExists;
    }
}
