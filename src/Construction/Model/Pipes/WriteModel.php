<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Nette\PhpGenerator\PsrPrinter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class WriteModel
{
    /**
     * PHP PSR Printer
     *
     * @var \Nette\PhpGenerator\PsrPrinter
     */
    protected $printer;

    /**
     * Application filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * WriteModel constructor.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  \Nette\PhpGenerator\PsrPrinter  $printer
     */
    public function __construct(Application $app, Filesystem $filesystem, PsrPrinter $printer)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
        $this->printer = $printer;
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
        $this->filesystem->ensureDirectoryExists($construction->model->directory(), true);

        $this->filesystem->put(
            $construction->model->path,
            $this->printer->printFile($construction->file)
        );

        return $next($construction);
    }
}
