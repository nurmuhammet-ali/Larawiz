<?php

namespace Larawiz\Larawiz\Scaffolding\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Larawiz;
use Larawiz\Larawiz\Scaffold;
use Symfony\Component\Yaml\Parser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;

abstract class BaseParserPipe
{
    /**
     * Application.
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
     * YAML File to check.
     *
     * @var null|string
     */
    protected $file;

    /**
     * YAML Parser
     *
     * @var \Symfony\Component\Yaml\Parser
     */
    protected $yaml;

    /**
     * If the pipeline should throw an exception if the scaffold file is not found.
     *
     * @var bool
     */
    protected $exceptionIfNoFile = false;

    /**
     * ParseYamlFileToScaffoldDatabase constructor.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  \Symfony\Component\Yaml\Parser  $yaml
     * @param  string|null  $file
     */
    public function __construct(Application $app, Filesystem $filesystem, Parser $yaml, string $file = null)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
        $this->file = $file;
        $this->yaml = $yaml;
    }

    /**
     * Handle the constructing scaffold data.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        if ($path = $this->getFilePathIfExists()) {

            if (! empty($contents = $this->getFileContents($path))) {
                $this->setRepository($scaffold, $contents);
            } else {
                throw $this->emptyFileException();
            }

        } elseif ($this->exceptionIfNoFile) {
            throw $this->fileNotFoundException();
        }

        return $next($scaffold);
    }

    /**
     * Return the section name from this pipe class name.
     *
     * @return string
     */
    protected function getSectionNameFromClassName()
    {
        return Str::lower(Str::between(class_basename($this), 'Parse', 'Data'));
    }

    /**
     * Return the filepath to parse, or null if none is found.
     *
     * @return null|string
     */
    protected function getFilePathIfExists()
    {
        foreach ($this->scaffoldFilesToRead() as $filepath) {
            if ($this->filesystem->exists($filepath)) {
                return $filepath;
            }
        }

        return false;
    }

    /**
     * Returns either the file to read from the developer, or all possible file variants.
     *
     * @return \Illuminate\Support\Collection|string[]
     */
    protected function scaffoldFilesToRead()
    {
        if ($this->file) {
            return collect([
                $this->app->basePath($this->file)
            ]);
        }

        return Larawiz::getFilePathsFor($this->getSectionNameFromClassName())
            ->map(function ($path) {
                return $this->app->basePath($path);
            });
    }

    /**
     * Get the file contents of the YAML file.
     *
     * @param  string  $path
     * @return array
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function getFileContents(string $path)
    {
        return $this->yaml->parse($this->filesystem->get($path), 1024 + 2048);
    }

    /**
     * Throw an exception for the empty file.
     *
     * @return \LogicException
     */
    protected function emptyFileException()
    {
        $class = $this->getSectionNameFromClassName();

        return new LogicException("The scaffold file for [{$class}] is empty.");
    }

    /**
     * Throw an exception for the file not found.
     *
     * @return \LogicException
     */
    protected function fileNotFoundException()
    {
        $class = $this->getSectionNameFromClassName();

        return new LogicException("The scaffold file for [{$class}] was not found.");
    }

    /**
     * Sets the raw data into the Repository.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  array  $data
     */
    abstract protected function setRepository(Scaffold $scaffold, array $data);
}
