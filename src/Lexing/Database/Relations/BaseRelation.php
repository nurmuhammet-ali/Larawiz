<?php

namespace Larawiz\Larawiz\Lexing\Database\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Fluent;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Model;
use LogicException;

/**
 * Class BaseRelation
 *
 * @package Larawiz\Larawiz\Lexing\Database\Relations
 *
 * @property string $name  Name of the relation
 * @property string $type  Type of the relation
 *
 * @property \Larawiz\Larawiz\Lexing\Database\Model $model  Model related.
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[] $methods  Code string translation for the relation in the Model class.
 */
abstract class BaseRelation extends Fluent
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
     * Far Relations
     *
     * @var array
     */
    public const THROUGH_RELATIONS = [
        'hasOneThrough', 'hasManyThrough'
    ];

    /**
     * Types of relations that needs a column in the model its declared.
     *
     * @var array
     */
    public const USES_COLUMN = [
        'belongsTo', 'morphTo',
    ];

    /**
     * Relations that need a Pivot table.
     *
     * @var array
     */
    public const USES_PIVOT = [
        'belongsToMany',
        'morphToMany',
    ];

    /**
     * Parent morph relations types.
     *
     * @var array
     */
    public const PARENT_MORPHS = [
        'morphOne'       => MorphOne::class,
        'morphMany'      => MorphMany::class,
        'morphedByMany'  => MorphToMany::class,
    ];

    /**
     * Child morph relations types.
     *
     * @var array
     */
    public const CHILD_MORPHS = [
        'morphTo'        => MorphTo::class,
        'morphToMany'    => MorphToMany::class,
    ];

    /**
     * Which relations types accepts "withDefault";
     *
     * @var array
     */
    public const ACCEPTS_WITH_DEFAULT = [
        'belongsTo',
        'morphTo',
        'morphOne',
        'hasOne',
        'hasOneThrough',
    ];

    /**
     * Simple type comparison for the relation class.
     *
     * @param  string|array  $type
     * @return bool
     */
    public function is($type)
    {
        return in_array($this->type, (array) $type, true);
    }

    /**
     * Returns the class using the relation.
     *
     * @return string
     */
    public function class()
    {
        return self::RELATION_CLASSES[$this->type];
    }

    /**
     * Returns if the relation type needs to declare a column in the model table.
     *
     * @return bool
     */
    public function needsTableColumn()
    {
        return in_array($this->type, static::USES_COLUMN, true);
    }

    /**
     * Returns if the relation type needs a pivot table to relate to other models.
     *
     * @return bool
     */
    public function needsPivotTable()
    {
        return in_array($this->type, static::USES_PIVOT, true);
    }

    /**
     * Returns if the relation type returns an Eloquent Collection of records.
     *
     * @return bool
     */
    public function returnsCollection()
    {
        return in_array($this->type, static::RETURN_COLLECTIONS, true);
    }

    /**
     * Returns if the relation type accepts "withDefault" method.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return void
     */
    public function validateWithDefault(Model $model)
    {
        $accepts = in_array($this->type, static::ACCEPTS_WITH_DEFAULT, true);

        if (! $accepts && $this->methods->contains('name', 'withDefault')) {
            throw new LogicException(
                "The [{$this->name}] relation type [{$this->type}] in [{$model->key}] doesn't accepts [withDefault]."
            );
        }
    }

    /**
     * Returns if the Has Relation is a Through relation.
     *
     * @return bool
     */
    public function isThrough()
    {
        return in_array($this->type, static::THROUGH_RELATIONS, true);
    }

    /**
     * Returns if the relation column declaration is nullable or not.
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->methods->contains(function (Method $method) {
            return in_array($method->name, Column::NULLABLE, true);
        });
    }

    /**
     * Returns if the relation is a parent morph relation.
     *
     * @return bool
     */
    public function isParentMorph()
    {
        return in_array($this->type, static::PARENT_MORPHS, true);
    }

    /**
     * Return if the relation is a child morph relation.
     *
     * @return bool
     */
    public function isChildMorph()
    {
        return in_array($this->type, static::CHILD_MORPHS, true);
    }

    /**
     * Returns if the relation is using a Pivot Model.
     *
     * @return bool
     */
    public function isUsingPivotModel()
    {
        return $this->using !== null;
    }

    /**
     * Returns if the current relation should use a pivot model.
     *
     * @return bool
     */
    public function shouldUseAutomaticPivot()
    {
        return ! $this->isUsingPivotModel();
    }

    /**
     * Returns if the current relation is the polymorphic parent of a relation, without pivot model.
     *
     * @param  string  $relationKey
     * @return bool
     */
    public function isPolymorphicParentOfWithoutPivot(string $relationKey)
    {
        return $this->relationKey === $relationKey
            && $this->is('morphToMany')
            && $this->shouldUseAutomaticPivot();
    }

    /**
     * Returns if the current relation is the polymorphic child of a relation, without pivot model
     *
     * @param  string  $relationKey
     * @return bool
     */
    public function isPolymorphicChildOfWithoutPivot(string $relationKey)
    {
        return $this->relationKey === $relationKey
            && $this->is('morphedByMany')
            && $this->shouldUseAutomaticPivot();
    }

    /**
     * Checks if the current relation has a Pivot model declared.
     *
     * @return bool
     */
    public function needsPivot()
    {
        return in_array($this->type, static::USES_PIVOT, true);
    }

    /**
     * Returns if the model is using withDefault.
     *
     * @return bool
     */
    public function usesWithDefault()
    {
        return $this->methods->contains('name', 'withDefault');
    }

    /**
     * Return the column should be nullable.
     *
     * @return bool
     */
    public function columnIsNullable()
    {
        return $this->methods->contains('name', 'nullable');
    }

    /**
     * Returns the relation methods without column-specific methods.
     *
     * @return \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[]
     */
    public function withoutColumnMethods()
    {
        return $this->methods->reject(function (Method $method) {
            return in_array($method->name, ['nullable', 'index', 'unique', 'uuid']);
        });
    }

    /**
     * Return the relations methods for the Model.
     *
     * @return \Larawiz\Larawiz\Lexing\Code\Method[]|\Illuminate\Support\Collection
     */
    public function relationMethods()
    {
        return $this->methods->reject(function (Method $method) {
            return in_array($method->name, ['nullable', 'index', 'unique', 'uuid']);
        });
    }
}
