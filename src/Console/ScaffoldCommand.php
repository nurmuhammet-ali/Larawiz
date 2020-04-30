<?php

namespace Larawiz\Larawiz\Console;

use Larawiz\Larawiz\Scaffold;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Writer\WriterPipeline;
use Larawiz\Larawiz\Scaffolding\ScaffoldParserPipeline;
use Larawiz\Larawiz\Scaffolding\Pipes\ParseDatabaseData;

class ScaffoldCommand extends BaseLarawizCommand
{
    /**
     * Sections enabled for scaffolding, with their pipeline.
     *
     * @var array
     */
    protected const ENABLED_SECTIONS = [
        'db' => ParseDatabaseData::class,
    ];

    /**
     * Application filesystem.
     *
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'larawiz:scaffold
                            {--db= : Database YAML file to parse}
                            {--no-backup : Runs without creating project backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold your next big idea.';

    /**
     * Create a new console command instance.
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
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle()
    {
        parent::handle();

        $this->setCommandInstanceInContainer();

        if ($this->option('no-backup')) {
            $this->warn('No project files will be backed up.');
        }
        else {
            $this->backupDirectories();
            $this->info('Making a backup of your project files and cleaning migrations.');
        }

        $this->info('Scaffolding your project, it will take a little time...');

        $this->write($this->parse());

        $this->info('Your scaffold is ready. Happy coding!');
    }

    /**
     * Sets this command instance into the Service Container.
     *
     * @return void
     */
    protected function setCommandInstanceInContainer()
    {
        $this->getLaravel()->instance(static::class, $this);
    }

    /**
     * Parse the YAML files into Scaffold-able data.
     *
     * @return \Larawiz\Larawiz\Scaffold
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function parse()
    {
        $pipeline = $this->getLaravel()->make(ScaffoldParserPipeline::class);

        $this->maySetCustomScaffoldFiles();

        return $this->getLaravel()->instance(
            Scaffold::class, $pipeline->send(Scaffold::make())->thenReturn()
        );
    }

    /**
     * Sets the YAML file to parse manually.
     *
     * @return void
     */
    protected function maySetCustomScaffoldFiles()
    {
        foreach (static::ENABLED_SECTIONS as $option => $parser) {
            if ($file = $this->option($option)) {
                $this->getLaravel()->when($parser)->needs('$file')->give($file);
            }
        }
    }

    /**
     * Write the project files from the parsed scaffold data.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @return Scaffold
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function write(Scaffold $scaffold)
    {
        return $this->getLaravel()->make(WriterPipeline::class)->send($scaffold)->thenReturn();
    }

    /**
     * Backup the application and database directories.
     *
     * @return void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function backupDirectories()
    {
        $this->getLaravel()->make(ApplicationBackup::class)->backup();
    }
}
