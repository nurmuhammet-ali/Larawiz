<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Nette\PhpGenerator\PsrPrinter;
use Nette\PhpGenerator\PhpNamespace;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Console\Kernel;
use Larawiz\Larawiz\Lexing\Database\Model;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
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

            if ($this->observerExists($construction->model)) {
                $this->deleteObserver($construction->model);
            }

            $this->console->call('make:observer', [
                'name' => $construction->model->class . 'Observer',
                '--model' => $construction->model->relativeNamespace
            ]);
        }

        return $next($construction);
    }

    /**
     * Check if the observer exists.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return bool
     */
    protected function observerExists(Model $model)
    {
        return $this->filesystem->exists(
            $this->app->path('Observers' . DS . $model->class . 'Observer.php')
        );
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
