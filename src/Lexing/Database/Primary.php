<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use Illuminate\Support\Collection;

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
     * Returns if the Primary uses an incrementing column.
     *
     * @return bool
     */
    public function usesIncrementing()
    {
        return $this->column && in_array($this->column->type, self::PRIMARY_COLUMN_METHODS, true);
    }

    /**
     * Returns if the column definition should be considered a primary key.
     *
     * @param  array  $columnsLines
     * @return bool
     */
    public static function hasColumnWithPrimary(array $columnsLines)
    {
        foreach ($columnsLines as $line) {
            if (Str::contains($line, static::PRIMARY_COLUMN_METHODS)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns if the Column line is using an incrementing column.
     *
     * @param  string  $columnLine
     * @return bool
     */
    public static function hasIncrementingKey(string $columnLine)
    {
        return Str::contains($columnLine, static::PRIMARY_COLUMN_METHODS);
    }
}
