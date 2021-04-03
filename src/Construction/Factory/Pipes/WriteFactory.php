<?php

namespace Larawiz\Larawiz\Construction\Factory\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Construction\Factory\FactoryConstruction;
use Nette\PhpGenerator\PsrPrinter;

class WriteFactory
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
     * Handle the factory construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Factory\FactoryConstruction  $construction
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(FactoryConstruction $construction, Closure $next)
    {
        $this->filesystem->ensureDirectoryExists($this->app->databasePath('factories'), true);

        $path = $this->app->databasePath('factories') . DIRECTORY_SEPARATOR . $construction->class->getName() . '.php';

        $this->filesystem->put($path, $this->printer->printFile($construction->file));

        return $next($construction);
    }
}
