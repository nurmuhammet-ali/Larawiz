<?php

namespace Larawiz\Larawiz;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;

use const DIRECTORY_SEPARATOR as DS;

class Larawiz
{
    /**
     * PHP Stub directories.
     *
     * @var string
     */
    public const STUB_DIR = __DIR__ . DS . '..' . DS . 'stubs';

    /**
     * The Larawiz backup directory inside the project storage path.
     *
     * @var string
     */
    public const BACKUPS_DIR = 'larawiz' . DS . 'backups';

    /**
     * Files valid for scaffolding, in order of precedence.
     *
     * @var array
     */
    public const FILES = [
        'database' => ['database', 'db', 'model', 'models'],
        'http'     => ['http', 'controller', 'controllers'],
        'auth'     => ['auth', 'authentication', 'authorization'],
    ];

    /**
     * Valid extensions for YAML files.
     *
     * @var array
     */
    public const EXTENSIONS = ['yml', 'yaml'];

    /**
     * Path of Larawiz files from the project's base path.
     *
     * @var string
     */
    public const PATH = 'larawiz';

    /**
     * Returns file paths that Larawiz uses for scaffolding for a given section.
     *
     * @param  string  $section
     * @return array
     */
    public static function getFilePathsFor(string $section)
    {
        return static::crossJoinFileExtensions(collect(static::FILES[$section]));
    }

    /**
     * Get all possible file paths for scaffold files.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getFilePaths()
    {
        return static::crossJoinFileExtensions(collect(static::FILES)->flatten());
    }

    /**
     * Cross joins the files with the extensions so every file variant as all YAML extensions.
     *
     * @param  \Illuminate\Support\Collection  $files
     * @return \Illuminate\Support\Collection
     */
    protected static function crossJoinFileExtensions(Collection $files)
    {
        return $files
            ->crossJoin(static::EXTENSIONS)
            ->map(function ($path) {
                return self::makePath([
                    static::PATH, implode('.', $path),
                ]);
            });
    }

    /**
     * Returns the Larawiz scaffold files path.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $application
     * @return string
     */
    public static function getBasePath(Application $application)
    {
        return $application->basePath() . DS . static::PATH;
    }

    /**
     * Returns the samples directory path.
     *
     * @param  string|null  $file
     * @return string
     */
    public static function samplePath(string $file = null)
    {
        $path = __DIR__ . DS . static::makePath(['..', 'samples']);

        if ($file) {
            return $path . DS . $file;
        }

        return $path;
    }

    /**
     * Makes a valid local path from an array.
     *
     * @param  array  $directories
     * @return string
     */
    public static function makePath(array $directories)
    {
        return implode(DS, array_map(function ($directories) {
            return trim($directories, ' \\/');
        }, $directories));
    }

    /**
     * Return the full path for a dummy file.
     *
     * @param  string  $name
     * @return string
     */
    public static function getDummyPath(string $name)
    {
        return static::STUB_DIR . DS . $name;
    }
}
