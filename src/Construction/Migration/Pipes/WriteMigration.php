<?php

namespace Larawiz\Larawiz\Construction\Migration\Pipes;

use Closure;
use Illuminate\Support\Str;
use Nette\PhpGenerator\PsrPrinter;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Lexing\Database\Migration;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Construction\Migration\MigrationConstruction;

class WriteMigration
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
     * Handle the migration construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Migration\MigrationConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(MigrationConstruction $construction, Closure $next)
    {
        $this->filesystem->put(
            $this->getPath($construction->migration),
            $this->printer->printFile($construction->file)
        );

        return $next($construction);
    }

    /**
     * Returns the path for the Factory file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @return string
     */
    protected function getPath(Migration $migration)
    {
        return $this->app->databasePath('migrations' . DIRECTORY_SEPARATOR . $migration->filename() . '.php');
    }
}
