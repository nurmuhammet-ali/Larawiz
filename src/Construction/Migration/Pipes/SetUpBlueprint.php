<?php

namespace Larawiz\Larawiz\Construction\Migration\Pipes;

use Closure;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Nette\PhpGenerator\ClassType;
use Illuminate\Database\Migrations\Migration;
use Larawiz\Larawiz\Lexing\Database\Migration as MigrationLexing;
use Larawiz\Larawiz\Construction\Migration\MigrationConstruction;

class SetUpBlueprint
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
        $construction->class->addMethod('up')
            ->addComment('Run the migrations.')
            ->addComment('')
            ->addComment('@return void')
            ->addBody($this->manageUpMethod($construction->migration));

        return $next($construction);
    }

    /**
     * Add the code schema for creating a new table.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @return string
     */
    protected function manageUpMethod(MigrationLexing $migration)
    {
        $start = '        Schema::create(\'' . $migration->table . '\', function (Blueprint $table) {';

        if ($migration->comment) {
            $start = '        // ' . $migration->comment . "\n" . trim($start);
        }

        return $start .
            $this->createColumns($migration) .
            $this->createPrimary($migration) .
            $this->createIndexes($migration) .
            "\n});";
    }

    /**
     * Creates columns for the table.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @return string
     */
    protected function createColumns(MigrationLexing $migration)
    {
        $string = '';

        foreach ($migration->columns as $column) {
            $string .= "\n    \$table->$column;";

            if ($column->comment) {
                $string .= ' // ' . $column->comment;
            }
        }

        return $string;
    }

    /**
     * Creates a Primary key.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @return string
     */
    protected function createPrimary(MigrationLexing $migration)
    {
        if ($migration->primary) {
            $string = "\n\n    \$table->primary('{$migration->primary}');";
        }

        return $string ?? '';
    }

    /**
     * Create the indexes.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @return string
     */
    protected function createIndexes(MigrationLexing $migration)
    {
        if ($migration->indexes->isNotEmpty()) {
            $string = "\n\n";

            foreach ($migration->indexes as $index) {
                $string .= "    {$index}\n";
            }
        }

        return $string ?? '';
    }

}
