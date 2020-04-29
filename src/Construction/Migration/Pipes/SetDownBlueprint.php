<?php

namespace Larawiz\Larawiz\Construction\Migration\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Migration\MigrationConstruction;

class SetDownBlueprint
{
    /**
     * Handle the migration construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Migration\MigrationConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(MigrationConstruction $construction, Closure $next)
    {
        $construction->class->addMethod('down')
            ->addComment('Reverse the migrations.')
            ->addComment('')
            ->addComment('@return void')
            ->addBody(
                "        Schema::dropIfExists('{$construction->migration->table}');"
            );

        return $next($construction);
    }
}
