<?php

namespace Larawiz\Larawiz\Writer\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Migration\MigrationConstruction;
use Larawiz\Larawiz\Construction\Migration\MigrationConstructorPipeline;
use Larawiz\Larawiz\Scaffold;

class WriteMigrations
{
    /**
     * Model Constructor Pipeline.
     *
     * @var \Larawiz\Larawiz\Construction\Migration\MigrationConstructorPipeline
     */
    protected $pipeline;

    /**
     * WriteDatabaseModels constructor.
     *
     * @param  \Larawiz\Larawiz\Construction\Migration\MigrationConstructorPipeline  $pipeline
     */
    public function __construct(MigrationConstructorPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    /**
     * Handle writing the scaffold files.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        if (isset($scaffold->database->migrations)) {
            foreach ($scaffold->database->migrations as $migration) {
                $this->pipeline->send(new MigrationConstruction([
                    'migration' => $migration,
                ]))->thenReturn();
            }
        }

        return $next($scaffold);
    }
}
