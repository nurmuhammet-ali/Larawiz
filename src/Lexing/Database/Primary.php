<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;

/**
 * Class Primary
 *
 * @package Larawiz\Larawiz\Parser\Eloquent
 *
 * @property bool $using
 * @property bool $default  If the primary key is not Laravel's default.
 *
 * @property null|string $type
 * @property null|bool $incrementing
 *
 * @property null|\Larawiz\Larawiz\Lexing\Database\Column $column
 */
class Primary extends Fluent
{
    /**
     * Methods that define primary keys.
     *
     * @var array
     */
    public const PRIMARY_COLUMN_METHODS = [
        'id',
        'increments',
        'integerIncrements',
        'tinyIncrements',
        'smallIncrements',
        'mediumIncrements',
        'bigIncrements',
    ];

    /**
     * All of the attributes set on the fluent instance.
     *
     * @var array
     */
    protected $attributes = [
        'using' => false,
    ];

    /**
     * Returns if the primary key is default;
     *
     * @return bool
     */
    public function isDefault()
    {
        return $this->column->name === 'id' && $this->column->type === 'id';
    }

    /**
     * Creates a Primary ID Column from the a line.
     *
     * @param  null|string  $line
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    public static function createFromId(?string $line)
    {
        // If the line is null, we will just create the ID column as default. Otherwise,
        // we can just issue whatever the line includes.
        return Column::fromLine('id', $line);
    }
}
