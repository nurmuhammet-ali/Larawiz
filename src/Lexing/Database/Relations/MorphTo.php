<?php

namespace Larawiz\Larawiz\Lexing\Database\Relations;

use Illuminate\Support\Str;

/**
 * @property string $columnName  The column name to create in the migration file.
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Model[] $models  Models related.
 */
class MorphTo extends BaseRelation
{
    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['type'] = 'morphTo';
        $this->attributes['models'] = collect();
        $this->attributes['isUuid'] = false;

        parent::__construct($attributes);
    }

    /**
     * Returns if the relation has been issued manually with an UUID.
     *
     * @return bool
     */
    public function isSetHasUuid()
    {
        return $this->methods->contains('arguments.value', 'nullable');
    }

    /**
     * Return the migration methods for the MorphTo column.
     *
     * @param  bool  $asUuid
     * @return string
     */
    public function migrationMethods(bool $asUuid = false)
    {
        $string = Str::of('morphs');

        if ($this->methods->contains('name', 'nullable')) {
            $string = $string->studly()->prepend('nullable');
        }

        if ($asUuid || $this->methods->contains('name', 'uuid')) {
            $string = $string->studly()->prepend('uuid');
        }

        return $string->finish(':')->__toString();
    }
}
