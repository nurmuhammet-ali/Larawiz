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
}
