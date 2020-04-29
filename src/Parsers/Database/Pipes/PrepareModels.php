<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Model;
use Illuminate\Contracts\Foundation\Application;
use const DIRECTORY_SEPARATOR;

class PrepareModels
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * ParseModelsData constructor.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
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
        $namespace = $scaffold->rawDatabase->get('namespace');

        foreach ($scaffold->rawDatabase->get('models') as $name => $model) {
            $scaffold->database->models->put($name, $this->createModel($name, $namespace));
        }

        // This is a good opportunity to check all models for duplicate class names and
        // bail out from scaffolding. If we proceed, some parts of the scaffolding may
        // not work properly, or overwrite the last model data over the first model.
        $this->ensureNoModelsClassDuplicated($scaffold->database->models);

        return $next($scaffold);
    }

    /**
     * Creates the Model instance with some basic information.
     *
     * @param  string  $name
     * @param  null|string  $namespace
     * @return \Larawiz\Larawiz\Lexing\Database\Model
     */
    protected function createModel(string $name, ?string $namespace = null)
    {
        $name = trim($name, '\\');
        $namespace = trim($namespace, '\\');

        return Model::make([
            'key' => $name,
            'class' => Str::afterLast($name, '\\'),
            'path' => $this->modelPath($name, $namespace),
            'namespace' => $this->modelNamespace($name, $namespace),
            'relativeNamespace' => $namespace ? $namespace . '\\' . $name : $name,
        ]);
    }

    /**
     * Ensures no class model name is duplicated before continuing.
     *
     * @param  \Illuminate\Support\Collection  $models
     */
    protected function ensureNoModelsClassDuplicated(Collection $models)
    {
        // First, we will normalize as lowercase all models names to avoid false negatives.
        // After that, we will check the duplicated classes. If there is any, we will tell
        // the developer which classes are duplicated in the list and the offending keys.
        $duplicates = $models->map->lowercase()->duplicates();

        if ($duplicates->isNotEmpty()) {
            $class = $models->get($duplicates->keys()->first())->class;
            $keys = $duplicates->keys()->implode(', ');

            throw new LogicException("The model class name [{$class}] is duplicated in [{$keys}].");
        }
    }

    /**
     * Returns the model Namespace.
     *
     * @param  string  $name
     * @param  null|string  $namespace
     * @return string
     */
    protected function modelNamespace(string $name, ?string $namespace)
    {
        $namespace = Str::of($this->app->getNamespace())->append($namespace)->trim('\\')->__toString();

        // If the model name has a namespace, we will remove the namespace from the name
        // and prepend it to issued namespace if it exists. If not we will just use the
        // name as the model class name, and get the rest of model data like the path.
        if (Str::contains($name, '\\')) {
            $namespace .= Str::of($name)->beforeLast('\\')->start('\\')->__toString();
        }

        return $namespace;
    }

    /**
     * Returns the model path.
     *
     * @param  string  $name
     * @param  null|string  $namespace
     * @return string
     */
    protected function modelPath(string $name, ?string $namespace)
    {
        return Str::of($this->app->path())
            ->finish(DIRECTORY_SEPARATOR)
            ->append($namespace)
            ->replace('\\', DIRECTORY_SEPARATOR)
            ->finish(DIRECTORY_SEPARATOR)
            ->append($name)
            ->finish('.php')
            ->__toString();

    }
}
