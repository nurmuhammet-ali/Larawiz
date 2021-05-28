<?php
/*
 * This Pipeline extends the normal Pipeline of Laravel but we use Dependency
 * Injection to receive the scaffold command instance and push the progress
 * bar by each of the pipes that has run successfully inside the pipeline.
 */

namespace Larawiz\Larawiz;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Larawiz\Larawiz\Console\ScaffoldCommand;

abstract class ConsoleAwarePipeline extends Pipeline
{
    /**
     * Scaffold Command
     *
     * @var \Larawiz\Larawiz\Console\ScaffoldCommand
     */
    protected $command;

    /**
     * Progress Bar
     *
     * @var \Symfony\Component\Console\Helper\ProgressBar
     */
    protected $bar;

    /**
     * Create a new class instance.
     *
     * @param  \Larawiz\Larawiz\Console\ScaffoldCommand  $command
     * @param  \Illuminate\Contracts\Container\Container|null  $container
     * @return void
     */
    public function __construct(ScaffoldCommand $command, Container $container = null)
    {
        parent::__construct($container);
        $this->command = $command;

        $this->bar = $this->command->getOutput()->createProgressBar(count($this->pipes));
    }

    /**
     * Creates a progress bar.
     *
     * @return void
     */
    protected function createProgressBar()
    {
        if (property_exists($this, 'progressName')) {
            $this->command->getOutput()->comment($this->progressName);
        }

        $this->bar->start();
    }

    /**
     * Pushes the progress bar to completion.
     *
     * @return void
     */
    protected function pushProgressBar()
    {
        $this->bar->advance();
    }

    /**
     * @inheritDoc
     */
    public function then(Closure $destination)
    {
        $this->createProgressBar();

        return parent::then($destination);
    }

    /**
     * @inheritDoc
     */
    protected function handleCarry($carry)
    {
        $this->pushProgressBar();

        return parent::handleCarry($carry);
    }
}
