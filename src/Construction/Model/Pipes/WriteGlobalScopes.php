<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Larawiz;
use Larawiz\Larawiz\Lexing\Database\Model;
use LogicException;

use const DIRECTORY_SEPARATOR as DS;

class WriteGlobalScopes
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Application Filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Creates a new WriteRepository instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(Application $app, Filesystem $filesystem)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
    }

    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        foreach ($construction->model->globalScopes as $scope) {

            $path = $this->getPath($construction->model, $scope);

            $this->filesystem->delete($path);

            $this->writeScope($construction->model, $scope, $path);
        }

        return $next($construction);
    }

    /**
     * Returns the path for the Global Scope file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $scope
     * @return string
     */
    protected function getPath(Model $model, string $scope)
    {
        if (Str::contains($scope, '\\')) {
            throw new LogicException("Scopes can only be set as class name, [{$scope}] issued in [{$model->key}].");
        }

        return $this->app->path('Scopes' . DS . $model->class . DS . $scope . '.php');
    }

    /**
     * Writes the Scope.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $scope
     * @param  string  $path
     * @return void
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function writeScope(Model $model, string $scope, string $path)
    {
        $directory = Str::beforeLast($path, DS);

        $this->filesystem->ensureDirectoryExists($directory, true);

        $this->filesystem->put($path, $this->getReplacedStubContents($scope, $model));
    }

    /**
     * Replaces the stub contents with the model class name and namespace.
     *
     * @param  string  $name
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return string|string[]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getReplacedStubContents(string $name, Model $model)
    {
        $contents = $this->filesystem->get(Larawiz::getDummyPath('DummyScope.stub'));

        return str_replace([
            '{DummyNamespace}',
            '{DummyModel}',
            '{dummyModel}',
            '{DummyScope}',
            '{DummyModelNamespace}',
        ], [
            $this->app->getNamespace() . 'Scopes\\' . $model->class,
            $model->class,
            Str::camel($model->class),
            $name,
            $model->fullNamespace(),
        ], $contents);
    }
}
