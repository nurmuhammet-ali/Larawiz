<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;
use Illuminate\Database\Eloquent\Model as EloquentModel;

/**
 * Class Timestamps
 *
 * @package Larawiz\Larawiz\Parser\Eloquent
 *
 * @property bool $using
 * @property null|string $createdAtColumn
 * @property null|string $updatedAtColumn
 */
class Timestamps extends Fluent
{
    /**
     * Touching timestamps for Models.
     *
     * @var array
     */
    public const TIMESTAMPS = [
        'timestamps','timestampsTz'
    ];

    /**
     * All of the attributes set on the fluent instance.
     *
     * @var array
     */
    protected $attributes = [
        'using' => false,
        'createdAtColumn' => EloquentModel::CREATED_AT,
        'updatedAtColumn' => EloquentModel::UPDATED_AT,
    ];

    /**
     * Check if the column name is being used in the native timestamping.
     *
     * @param  string  $name
     * @return bool
     */
    public function isTimestamps(string $name)
    {
        return in_array($name, [$this->createdAtColumn, $this->updatedAtColumn], true);
    }

    /**
     * Check if the column name is NOT being used in the native timestamping.
     *
     * @param  string  $name
     * @return bool
     */
    public function notTimestamps(string $name)
    {
        return ! $this->isTimestamps($name);
    }

    /**
     * Checks if is using CREATED_AT timestamp.
     *
     * @return bool
     */
    public function usingCreatedAt()
    {
        return (bool) $this->createdAtColumn;
    }

    /**
     * Checks if is using UPDATED_AT timestamp.
     *
     * @return bool
     */
    public function usingUpdatedAt()
    {
        return (bool) $this->updatedAtColumn;
    }

    /**
     * Check if the CREATED_AT is default.
     *
     * @return bool
     */
    public function usingDefaultCreatedAt()
    {
        return $this->createdAtColumn === EloquentModel::CREATED_AT;
    }

    /**
     * Check if the UPDATED_AT is default.
     *
     * @return bool
     */
    public function usingDefaultUpdatedAt()
    {
        return $this->updatedAtColumn === EloquentModel::UPDATED_AT;
    }
}
