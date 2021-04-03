<?php

namespace Larawiz\Larawiz\Console;

use ErrorException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Larawiz;
use RuntimeException;

use const DIRECTORY_SEPARATOR;

class ApplicationBackup
{
    /**
     * Application filesystem.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Create a new console command instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(Application $app, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->app = $app;
    }

    /**
     * Backups project files.
     *
     * @return void
     */
    public function backup()
    {
        $backupDir = $this->backupDir();

        $this->makeBackupDirectory($backupDir);

        $this->moveCurrentProjectFiles($backupDir);
    }

    /**
     * Returns the target backup directory.
     *
     * @return string
     */
    protected function backupDir()
    {
        return implode(DIRECTORY_SEPARATOR, [
            $this->app->storagePath(), Larawiz::BACKUPS_DIR, now()->format('Y-m-d_His'),
        ]);
    }

    /**
     * Creates the backup directory.
     *
     * @param  string  $dir
     */
    protected function makeBackupDirectory(string $dir)
    {
        try {
            $this->filesystem->ensureDirectoryExists($dir, 0755, true);
        } catch (ErrorException $exception) {
            throw new RuntimeException(
                "The directory [$dir] couldn't be made to backup your app. Check permissions."
            );
        }
    }

    /**
     * Moves the project files into the backup directory.
     *
     * @param  string  $backupDir
     */
    protected function moveCurrentProjectFiles(string $backupDir)
    {
        foreach ($this->getDirectoriesToBackup() as $dir) {
            $moved = $this->filesystem->moveDirectory($dir, $this->backupDirectory($backupDir, $dir));

            // Windows doesn't like to rename directories, so if we fail, we'll
            // do it the old way: copy the directory and delete the source dir
            // while keeping that directory. If not, we can ensure it exists.
            if (!$moved) {
                $this->filesystem->copyDirectory($dir, $this->backupDirectory($backupDir, $dir));
                $this->filesystem->deleteDirectory($dir, true);
            } else {
                $this->filesystem->ensureDirectoryExists($dir, 0755, true);
            }
        }
    }

    /**
     * Returns a list of directories to backup.
     *
     * @return array
     */
    protected function getDirectoriesToBackup()
    {
        return [
            $this->app->path('Models'),
            $this->app->databasePath('migrations'),
            $this->app->databasePath('factories'),
            $this->app->databasePath('seeders'),
        ];
    }

    /**
     * Inject the Larawiz Back up directory to the final path.
     *
     * @param  string  $backupDir
     * @param  string  $path
     * @return string
     */
    protected function backupDirectory(string $backupDir, string $path)
    {
        return Str::of(rtrim($path, DIRECTORY_SEPARATOR))
            ->replace($this->app->basePath(), $backupDir)
            ->__toString();
    }
}
