<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Larawiz;
use Larawiz\Larawiz\Lexing\Database\QuickCast;

class WriteCasts
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
        foreach ($construction->model->quickCasts as $cast) {
            if ($this->filesystem->exists($cast->path)) {
                continue;
            }

            $this->writeCastFile($cast, $this->castFileContents($cast));
        }

        return $next($construction);
    }

    /**
     * Makes the contents for the trait file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\QuickCast  $cast
     *
     * @return string|string[]
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function castFileContents(QuickCast $cast)
    {
        return str_replace('DummyCast', $cast->class, $this->filesystem->get(Larawiz::getDummyPath('DummyCast.stub')));
    }

    /**
     * Writes the trait to the trait file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\QuickCast  $cast
     * @param  string  $contents
     */
    protected function writeCastFile(QuickCast $cast, string $contents)
    {
        $this->filesystem->ensureDirectoryExists($cast->directory(), true);

        $this->filesystem->put($cast->path, $contents);
    }
}
