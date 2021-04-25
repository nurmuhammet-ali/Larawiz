<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;
use Larawiz\Larawiz\Lexing\HasNamespaceAndPath;

/**
 * @property boolean $external
 */
class QuickTrait extends Fluent
{
    use HasNamespaceAndPath;

    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['internal'] = true;

        parent::__construct($attributes);
    }
}
