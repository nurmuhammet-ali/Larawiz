<?php

namespace Larawiz\Larawiz\Lexing\Database\Relations;

/**
 * @property null|\Larawiz\Larawiz\Lexing\Database\Model $using
 */
class BelongsToMany extends BaseRelation
{
    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['type'] = 'belongsToMany';

        parent::__construct($attributes);
    }
}
