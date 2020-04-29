<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Larawiz;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Lexing\Database\QuickTrait;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class WriteTraits
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
        foreach ($construction->model->quickTraits->filter->internal as $trait) {

            if ($this->filesystem->exists($trait->path)) {
                continue;
            }

            $this->writeTraitFile($trait, $this->traitFileContents($trait));
        }

        return $next($construction);
    }

    /**
     * Makes the contents for the trait file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\QuickTrait  $trait
     * @return string|string[]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function traitFileContents(QuickTrait $trait)
    {
        return str_replace(['DummyTrait', 'DummyModelNamespace'], [
            $trait->class,
            $trait->namespace,
        ], $this->filesystem->get(Larawiz::getDummyPath('DummyTrait.stub')));
    }

    /**
     * Writes the trait to the trait file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\QuickTrait  $trait
     * @param  string  $contents
     */
    protected function writeTraitFile(QuickTrait $trait, string $contents)
    {
        $this->filesystem->ensureDirectoryExists($trait->directory(), true);

        $this->filesystem->put($trait->path, $contents);
    }
}
