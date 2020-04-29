<?php

namespace Larawiz\Larawiz\Console;

use SplFileInfo;
use Larawiz\Larawiz\Larawiz;
use Illuminate\Filesystem\Filesystem;
use const DIRECTORY_SEPARATOR as DS;

class SampleCommand extends BaseLarawizCommand
{
    /**
     * The default message when files are copied.
     *
     * @var string
     */
    protected const MESSAGE_COPIED = 'Scaffold sample files published. Happy coding!';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larawiz:sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Conveniently creates sample YAML files to kick off your project.';

    /**
     * Filesystem implementation.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Create a new command instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    /**
     * Execute the console command.
     *
     * @return mixed|void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        parent::handle();

        if ($this->alreadyHasScaffoldFiles() && $this->dontOverwriteFiles()) {
            return;
        }

        $this->copySamplesFilesToLarawizPath();

        foreach ($this->filesInScaffoldDirectory() as $file) {
            $this->line(' * ' . $this->larawizDirectory() . DS . $file);
        }

        // We will conveniently instance the command to get the command name.
        $command = $this->getLaravel()->make(ScaffoldCommand::class)->getName();

        $this->line("Once you're done, use [{$command}] to create your project.");
    }

    /**
     * Detect if there are already scaffold files present in the project.
     *
     * @return bool
     */
    protected function alreadyHasScaffoldFiles()
    {
        $larawizDirectory = $this->larawizDirectory();

        if ($this->filesystem->exists($larawizDirectory)) {

            $files = $this->filesystem->files($larawizDirectory);

            foreach ($files as $file) {
                if ($this->filenameCollidesWithIncomingSample($file)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * If files are already present, ask if it should overwrite them.
     *
     * @return bool
     */
    protected function dontOverwriteFiles()
    {
        return ! $this->confirm('Scaffold files already exists! Do you want to overwrite them?');
    }

    /**
     * Returns the files in the scaffold directory.
     *
     * @return \Symfony\Component\Finder\SplFileInfo[]
     */
    protected function filesInScaffoldDirectory()
    {
        return $this->filesystem->files($this->getLaravel()->basePath('larawiz'));
    }

    /**
     * Copy Larawiz sample files to the project's Larawiz directory.
     *
     * @return void
     */
    protected function copySamplesFilesToLarawizPath()
    {
        if ($this->filesystem->copyDirectory(Larawiz::samplePath(), $this->larawizDirectory())) {
            $this->info(static::MESSAGE_COPIED . "\n");
        }
    }

    /**
     * Returns if the filepath to sample already exists in the project path.
     *
     * @param  \SplFileInfo  $file
     * @return bool
     */
    protected function filenameCollidesWithIncomingSample(SplFileInfo $file)
    {
        return Larawiz::getFilePaths()->contains(Larawiz::PATH . DS . $file->getFilename());
    }

    /**
     * Return the directory used by Larawiz.
     *
     * @return string
     */
    protected function larawizDirectory()
    {
        return Larawiz::getBasePath($this->getLaravel());
    }
}
