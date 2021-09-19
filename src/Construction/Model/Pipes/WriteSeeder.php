<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Larawiz;
use Larawiz\Larawiz\Lexing\Database\Model;

use const DIRECTORY_SEPARATOR as DS;

class WriteSeeder
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
     * Creates a new WriteSeeder instance.
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
        if ($construction->model->seeder) {
            $this->filesystem->delete($path = $this->getPath($construction->model));
            $this->writeSeeder($construction->model, $path);
        }

        return $next($construction);
    }

    /**
     * Returns the path for the Global Scope file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return string
     */
    protected function getPath(Model $model)
    {
        return $this->app->databasePath('seeders' . DS . $model->class . 'Seeder.php');
    }

    /**
     * Writes the seeder file into the directory path.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $path
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function writeSeeder(Model $model, string $path)
    {
        $this->filesystem->ensureDirectoryExists($this->app->databasePath('seeders'));
        $this->filesystem->put($path, $this->getReplacedStubContents($model));
    }

    /**
     * Replaces the stub contents with the model class name and namespace.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getReplacedStubContents(Model $model)
    {
        $contents = $this->filesystem->get(Larawiz::getDummyPath('DummySeeder.stub'));

        return str_replace([
            '{DummyModel}',
            '{DummySeeder}',
            '{DummyModelNamespace}',
        ], [
            $model->class,
            $model->class . 'Seeder',
            $model->fullNamespace(),
        ], $contents);
    }
}
