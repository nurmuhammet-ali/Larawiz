<?php

namespace Larawiz\Larawiz\Lexing\Database\Relations;
use Illuminate\Support\Str;

/**
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[] $migrationMethods  Custom set of methods for the migration
 *
 * @property null|string $columnKey  The column it should use for accessing the model, if any.
 */
class BelongsTo extends BaseRelation
{
    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['type'] = 'belongsTo';
        $this->attributes['nullable'] = false;

        parent::__construct($attributes);
    }

    /**
     * Checks if the relation has a Column manually set to correlate.
     *
     * @return bool
     */
    public function hasColumnKey()
    {
        return $this->columnKey !== null;
    }

    /**
     * Returns column name from the target model based on the column key.
     *
     * @return null|string
     */
    public function getModelColumnFromColumnKey()
    {
        return Str::of($this->columnKey)->snake()->afterLast('_')->__toString();
    }
}
