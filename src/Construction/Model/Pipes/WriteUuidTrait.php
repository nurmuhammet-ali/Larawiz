<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Larawiz\Larawiz\Larawiz;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\ClassType;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class WriteUuidTrait
{
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
     * WriteTraits constructor.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $application
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(Application $application, Filesystem $filesystem)
    {
        $this->app = $application;
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
        if ($construction->model->hasUuidPrimaryKey()) {
            $this->copyUuidTrait();
            $this->addTraitToModel($construction->file, $construction->class);
        }

        return $next($construction);
    }

    /**
     * Copy the UUID trait alongside the application namespace.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function copyUuidTrait()
    {
        $this->filesystem->put($this->app->path('HasUuidPrimaryKey.php'), $this->getTraitContents());
    }

    /**
     * Return the trait contents.
     *
     * @return string|string[]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getTraitContents()
    {
        $contents = $this->filesystem->get(Larawiz::getDummyPath('HasUuidPrimaryKey.stub'));

        return str_replace('{DummyNamespace}', trim($this->app->getNamespace(), '\\'), $contents);
    }

    /**
     * Add the trait to the model class.
     *
     * @param  \Nette\PhpGenerator\PhpFile  $file
     * @param  \Nette\PhpGenerator\ClassType  $class
     */
    protected function addTraitToModel(PhpFile $file, ClassType $class)
    {
        $namespace = Str::of($this->app->getNamespace())->finish('\\')->append('HasUuidPrimaryKey');

        Arr::first($file->getNamespaces())->addUse($namespace);

        $class->addTrait($namespace);
    }

}
