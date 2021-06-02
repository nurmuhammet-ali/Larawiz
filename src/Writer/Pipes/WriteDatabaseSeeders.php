<?php

namespace Larawiz\Larawiz\Writer\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Seeder;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Scaffold;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

class WriteDatabaseSeeders
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
     * @var \Larawiz\Larawiz\Lexing\Database\Factory
     */
    protected $factory;

    /**
     * PHP PSR Printer
     *
     * @var \Nette\PhpGenerator\PsrPrinter
     */
    protected $printer;

    /**
     * Creates a new WriteRepository instance.
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
     * Handle writing the scaffold files.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        $file = new PhpFile();
        $method = $this->createSeederMethod($file);

        $calls = [];

        foreach ($scaffold->database->models as $model) {
            if ($model->seeder) {
                $calls[] = '// $this->call(' . $model->class . 'Seeder::class);';
            }
        }

        $method->addBody(implode("\n", $calls));

        $this->filesystem->ensureDirectoryExists($this->app->databasePath('seeders'), true);

        $this->filesystem->put(
            $this->app->databasePath('seeders' . DIRECTORY_SEPARATOR . 'DatabaseSeeder.php'),
            $this->printer->printFile($file)
        );

        return $next($scaffold);
    }

    /**
     * Creates the base run method of the main seeder.
     *
     * @param  \Nette\PhpGenerator\PhpFile  $file
     *
     * @return \Nette\PhpGenerator\Method
     */
    protected function createSeederMethod(PhpFile $file): Method
    {
        return $file
            ->addNamespace('Database\Seeders')
            ->addUse(Seeder::class)
            ->addClass('DatabaseSeeder')
            ->addExtend(Seeder::class)
            ->addMethod('run')
            ->addComment('Seed the application\'s database.')
            ->addComment('')
            ->addComment('@return void')
            ->addBody('// TODO: Uncomment and reorder the seeders.');
    }
}
