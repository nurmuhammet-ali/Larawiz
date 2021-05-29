<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;

/**
 * Class SoftDelete
 *
 * @package Larawiz\Larawiz\Lexing\Database
 *
 * @property bool $using
 * @property string $column
 */
class SoftDelete extends Fluent
{
    /**
     * Default column for soft deletes.
     *
     * @var string
     */
    public const COLUMN = 'deleted_at';

    /**
     * Soft deletes for Models.
     *
     * @var array
     */
    public const SOFT_DELETES = [
        'softDeletes', 'softdeletesTz'
    ];

    /**
     * All of the attributes set on the fluent instance.
     *
     * @var array
     */
    protected $attributes = [
        'using' => false,
        'column' => self::COLUMN,
    ];

    /**
     * Returns if the Soft Delete uses a non default column.
     *
     * @return bool
     */
    public function usesNonDefaultColumn()
    {
        return $this->column !== static::COLUMN;
    }
}
