<?php

namespace Larawiz\Larawiz\Construction\Migration\Pipes;

use Closure;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Larawiz\Larawiz\Construction\Migration\MigrationConstruction;
use Nette\PhpGenerator\PhpFile;

class CreateMigrationInstance
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
        $construction->file = new PhpFile;

        $construction->file->addUse(Migration::class);
        $construction->file->addUse(Blueprint::class);
        $construction->file->addUse(Schema::class);

        $construction->class = $construction->file->addClass($construction->migration->className());
        $construction->class->setExtends(Migration::class);

        return $next($construction);
    }
}
