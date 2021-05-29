<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;

/**
 * Class Index
 *
 * @package Larawiz\Larawiz\Lexing\Database
 *
 * @property null|string $name
 *
 * @property \Illuminate\Support\Collection|string[] $columns
 *
 * @property boolean $unique
 */
class Index extends Fluent
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
        $this->attributes['unique'] = false;

        parent::__construct($attributes);
    }

    /**
     * Returns a string representation of the Index.
     *
     * @return string
     */
    public function __toString()
    {
        $columns = $this->columns->map(function ($column) {
            return "'{$column}'";
        })->implode(', ');

        $string = '$table->' . ($this->unique ? 'unique' : 'index') . "([{$columns}]";

        if ($this->name) {
            $string .= ", '{$this->name}'";
        }

        return $string . ');';
    }
}
