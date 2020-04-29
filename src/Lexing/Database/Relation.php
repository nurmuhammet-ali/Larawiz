<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * Class Relation
 *
 * @package Larawiz\Larawiz\Parser\Eloquent
 *
 * @property string $name
 * @property string $type
 *
 * @property \Larawiz\Larawiz\Lexing\Database\Model $belongModel
 * @property \Larawiz\Larawiz\Lexing\Database\Model $hasModel
 * @property null|\Larawiz\Larawiz\Lexing\Database\Model $throughModel
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[] $methods
 *
 * @property \Larawiz\Larawiz\Lexing\Database\Column $belongingColumn
 *
 * @property \Illuminate\Support\Collection|string[] $withPivotColumns
 *
 * @property bool $withDefault
 *
 * @property null|\Larawiz\Larawiz\Lexing\Database\Model $usingPivot
 */
class Relation extends Fluent
{
    /**
     * All the available relations classes.
     *
     * @var array
     */
    public const RELATION_CLASSES = [
        'hasOne'         => HasOne::class,
        'hasOneThrough'  => HasOneThrough::class,
        'hasMany'        => HasMany::class,
        'hasManyThrough' => HasManyThrough::class,
        'belongsTo'      => BelongsTo::class,
        'belongsToMany'  => BelongsToMany::class,
        'morphOne'       => MorphOne::class,
        'morphMany'      => MorphMany::class,
        'morphTo'        => MorphTo::class,
        'morphToMany'    => MorphToMany::class,
        'morphedByMany'  => MorphToMany::class,
    ];

    /**
     * Relations that return an Eloquent Collection.
     *
     * @var array
     */
    public const RETURN_COLLECTIONS = [
        'hasMany',
        'hasManyThrough',
        'belongsToMany',
        'morphMany',
        'morphToMany',
        'morphedByMany',
    ];

    /**
     * Types of relations that needs a column in the model its declared.
     *
     * @var array
     */
    public const USES_COLUMN = [
        'belongsTo', 'morphsTo',
    ];

    /**
     * Relations that need a Pivot table.
     *
     * @var array
     */
    public const USES_PIVOT = [
        'belongsToMany',
        'morphedByMany',
    ];

    /**
     * All of the attributes set on the fluent instance.
     *
     * @var array
     */
    protected $attributes = [
        'withDefault' => false,
    ];

    /**
     * Returns if the relation type needs a pivot table.
     *
     * @return bool
     */
    public function usesPivot()
    {
        return in_array($this->type, static::USES_PIVOT, true);
    }

    /**
     * Checks if the Relation uses a Model as a Pivot.
     *
     * @return bool
     */
    public function usesModelAsPivot()
    {
        return (bool)$this->usingPivot;
    }

    /**
     * Checks if the Relation needs a Column in the model.
     *
     * @return bool
     */
    public function needsBelongingColumn()
    {
        return in_array($this->type, static::USES_COLUMN, true);
    }

    /**
     * Check if the relation doesn't need a Column in the model.
     *
     * @return bool
     */
    public function doesNotNeedsColumn()
    {
        return ! $this->needsBelongingColumn();
    }

    /**
     * Returns the class using the relation.
     *
     * @return string
     */
    public function class()
    {
        return self::RELATION_CLASSES[$this->attributes['type']];
    }

    /**
     * Checks if the the relation returns an Eloquent Collection when using a property accessor.
     *
     * @return bool
     */
    public function returnsCollection()
    {
        return in_array($this->attributes['type'], self::RETURN_COLLECTIONS, true);
    }

    /**
     * Returns the "belongsTo" column name.
     *
     * @return null|string
     */
    public function getBelongingToColumn()
    {
        // If the relation name is the same as the class, then it's okay to let Laravel figure it out.
        if ($this->name === $this->hasModel->singular()) {
            return null;
        }

        // Second, we will use the column name the user issued in the YAML, if is stored here.
        if ($this->belongingColumn->name) {
            return $this->belongingColumn->name;
        }

        // Otherwise, we will try to guess the name of the primary key of the model it belongs to.
        if ($this->hasModel->primary->using) {
            return $this->hasModel->singular() . '_' . $this->hasModel->primary->column->name;
        }

        // We don't have nor the name or primary key, so we will blindly guess the name as "{name}_id".
        return $this->name . '_id';
    }
}
