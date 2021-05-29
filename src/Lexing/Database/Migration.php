<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * Class Migration
 *
 * @package Larawiz\Larawiz\Parser\Eloquent
 *
 * @property string $table
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[] $columns
 * @property string $primary
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Index[] $indexes
 *
 * @property string $comment
 *
 * @property bool $fromGuessedPivot
 */
class Migration extends Fluent
{
    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['columns'] = collect();
        $this->attributes['indexes'] = collect();
        $this->attributes['fromGuessedPivot'] = false;

        parent::__construct($attributes);
    }

    /**
     * Returns the filename of the migration.
     *
     * @return string
     */
    public function filename()
    {
        return now()->format('Y_m_d_His') . '_' . Str::snake($this->className());
    }

    /**
     * Returns the class name of the migration.
     *
     * @return string
     */
    public function className()
    {
        return 'Create' . Str::studly($this->table) . 'Table';
    }
}
