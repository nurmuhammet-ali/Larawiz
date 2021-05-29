<?php

namespace Larawiz\Larawiz\Construction\Migration\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Migration\MigrationConstruction;
use Larawiz\Larawiz\Lexing\Database\Migration as MigrationLexing;

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
        $create = '        Schema::create(\'' . $migration->table . '\', function (Blueprint $table) {';

        if ($migration->comment) {
            $create = '        // ' . $migration->comment . "\n" . trim($create);
        }

        $create .= $this->createColumns($migration) . "\n});";

        if ($migration->indexes->isNotEmpty()) {
            $create .= "\n\n"
                . 'Schema::table(\'' . $migration->table . '\', function (Blueprint $table) {' . "\n"
                . $this->createIndexes($migration)
                . "\n});";
        }

        return $create;
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
     * Create the indexes.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Migration  $migration
     * @return string
     */
    protected function createIndexes(MigrationLexing $migration)
    {
        $string = [];

        foreach ($migration->indexes as $index) {
            $string[] = "    {$index}";
        }

        return implode("\n", $string);
    }

}
