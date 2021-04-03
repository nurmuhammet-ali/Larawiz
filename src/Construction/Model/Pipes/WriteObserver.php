<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Lexing\Database\Model;

use const DIRECTORY_SEPARATOR as DS;

class WriteObserver
{
    /**
     * Console.
     *
     * @var \Illuminate\Contracts\Console\Kernel
     */
    protected $console;

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * WriteModel constructor.
     *
     * @param  \Illuminate\Contracts\Console\Kernel  $console
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(Kernel $console, Application $app, Filesystem $filesystem)
    {
        $this->console = $console;
        $this->app = $app;
        $this->filesystem = $filesystem;
    }

    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->observer) {

            $this->deleteObserver($construction->model);

            $this->console->call('make:observer', [
                'name' => $construction->model->class . 'Observer',
                '--model' => $construction->model->getRelativeNamespaceWithoutModel()
            ]);
        }

        return $next($construction);
    }

    /**
     * Delete the observer.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     */
    protected function deleteObserver(Model $model)
    {
        $this->filesystem->delete(
            $this->app->path('Observers' . DS . $model->class . 'Observer.php')
        );
    }
}
