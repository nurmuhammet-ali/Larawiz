<?php

namespace Larawiz\Larawiz\Console;

use Illuminate\Console\Command;

abstract class BaseLarawizCommand extends Command
{
    /**
     * The common header for all Larawiz commands.
     *
     * @var string
     */
    protected const COMMAND_HEADER = "🧙‍‍ Larawiz";

    /**
     * Execute the console command.
     *
     * @return mixed|void
     */
    public function handle()
    {
        $this->line(self::COMMAND_HEADER);
        $this->line('');
    }
}
