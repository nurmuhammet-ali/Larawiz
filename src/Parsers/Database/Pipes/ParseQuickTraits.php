<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Helpers;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\QuickTrait;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseQuickTraits
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
            if (is_array($traits = $scaffold->rawDatabase->get("models.{$key}.traits"))) {
                $this->addTraitsToModel($model, $traits);
            }
        }

        // This is also a good opportunity to verify if any trait collides in name with
        // a Model class name in the same path. If that is the case, we must bail out
        // and tell the developer to rename or move the given trait for the model.
        $this->checkNotTraitCollidesWithModels($scaffold);

        return $next($scaffold);
    }

    /**
     * Add the traits to the model
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  array  $traits
     */
    protected function addTraitsToModel(Model $model, array $traits)
    {
        foreach ($traits as $trait) {
            $model->quickTraits->put($trait, $this->createTrait($trait));
        }
    }

    /**
     * Creates a Quick Trait.
     *
     * @param  string  $trait
     * @return \Larawiz\Larawiz\Lexing\Database\QuickTrait
     */
    protected function createTrait(string $trait)
    {
        $instance = new QuickTrait;

        // If the trait already exists we will create a reference and mark it as "external".
        $instance->external = $this->traitExists($trait);
        $instance->namespace = trim($trait, '\\');

        // If it's not external, we will set the proper namespace and path.
        if (! $instance->external) {
            [$instance->namespace, $instance->class] = Helpers::namespaceAndClass($trait, $this->namespace . '\\' . 'Models');
            $instance->path = Helpers::pathFromNamespace($instance->fullNamespace(), $this->path, $this->namespace);
        }

        return $instance;
    }

    /**
     * Intersect the model and traits path to detect duplicates.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @return void
     */
    protected function checkNotTraitCollidesWithModels(Scaffold $scaffold)
    {
        $intersected = $scaffold->database->models->map->path->intersect(
            $scaffold->database->models->map->quickTraits->flatten()->map->path
        )->unique()->keys();

        if ($intersected->count()) {
            throw new LogicException(
                'The following traits collide with the models: ' . $intersected->implode(', ') . '.'
            );
        }
    }

    /**
     * Check if the trait doesnt exists.
     *
     * @param  string  $trait
     * @return bool
     */
    protected function traitExists(string $trait): bool
    {
        // We will bail out if the trait is a class or interface.
        if (class_exists($trait) || interface_exists($trait)) {
            throw new LogicException("The [{$trait}] exists but is not a trait, but a class or interface.");
        }

        return trait_exists($trait);
    }

}
