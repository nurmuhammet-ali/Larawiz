<?php

namespace Larawiz\Larawiz\Lexing\Database;

use LogicException;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Code\Method;
use Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation;

/**
 * Class Column
 *
 * @package Larawiz\Larawiz\Parser\Eloquent
 *
 * @property string $name
 * @property string $type
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[] $methods
 * @property null|\Larawiz\Larawiz\Lexing\Database\Relations\BelongsTo|\Larawiz\Larawiz\Lexing\Database\Relations\MorphTo $relation
 *
 * @property bool $isNullable
 *
 * @property null|string $comment
 */
class Column extends Fluent
{
    /**
     * List of real integer methods behind the incrementing methods.
     *
     * @var array
     */
    public const INCREMENTING_TO_INTEGER = [
        'id'                => 'unsignedBigInteger',
        'increments'        => 'unsignedInteger',
        'integerIncrements' => 'unsignedInteger',
        'tinyIncrements'    => 'unsignedTinyInteger',
        'smallIncrements'   => 'unsignedSmallInteger',
        'mediumIncrements'  => 'unsignedMediumInteger',
        'bigIncrements'     => 'unsignedBigInteger',
    ];

    /**
     * Maps blueprint methods to the PHP type
     *
     * @var array
     */
    public const BLUEPRINT_TO_TYPE = [
        'int' => [
            'id',
            'increments',
            'integerIncrements',
            'tinyIncrements',
            'smallIncrements',
            'mediumIncrements',
            'bigIncrements',
            'integer',
            'unsignedInteger',
            'unsignedTinyInteger',
            'unsignedSmallInteger',
            'unsignedMediumInteger',
            'unsignedBigInteger',
        ],
        'float' => [
            'decimal',
            'double',
            'float',
            'point',
        ],
        'bool' => [
            'bool',
            'boolean',
        ],
        'array' => [
            'json',
            'jsonb'
        ],
        '\Illuminate\Support\Carbon' => [
            'date',
            'dateTime',
            'dateTimeTz',
            'time',
            'timeTz',
            'timestamp',
            'timestampTz',
            'year',
        ],
    ];

    /**
     * Maps the blueprint to Eloquent casting array. Strings are default null.
     *
     * @var array
     */
    public const BLUEPRINT_TO_CAST = [
        'integer' => [
            'id',
            'increments',
            'integerIncrements',
            'tinyIncrements',
            'smallIncrements',
            'mediumIncrements',
            'bigIncrements',
            'integer',
            'unsignedInteger',
            'unsignedTinyInteger',
            'unsignedSmallInteger',
            'unsignedMediumInteger',
            'unsignedBigInteger',
        ],
        'float' => [
            'decimal',
            'double',
            'float',
            'point',
        ],
        'bool' => [
            'bool',
            'boolean',
        ],
        'array' => [
            'json',
            'jsonb'
        ],
        'datetime' => [
            'date',
            'dateTime',
            'dateTimeTz',
            'time',
            'timeTz',
            'timestamp',
            'timestampTz',
            'year',
        ]
    ];

    /**
     * Map of dates that should be casted
     *
     * @var array
     */
    public const BLUEPRINT_TO_DATES = [
        'date',
        'dateTime',
        'dateTimeTz',
        'time',
        'timeTz',
        'timestamp',
        'timestampTz',
        'year',
    ];

    /**
     * Real column names for types with null names.
     *
     * @var array
     */
    public const DEFAULT_NAMES = [
        'id' => 'id',
        'rememberToken' => 'remember_token',
        'softDeletes' => SoftDelete::COLUMN,
    ];

    /**
     * Columns that shouldn't be commented in PHPDoc.
     *
     * @var array
     */
    public const UNCOMMENTABLE = [
        'nullableMorphs',
        'nullableUuid',
        'nullableUuidMorphs',
        'nullableTimestamps',
        'nullableTimestampsTz',
        'timestamps',
        'timestampsTz',
        'rememberToken',
        'morphs',
        'softDeletes',
        'softDeletesTz',
    ];

    /**
     * Nullable column definitions
     *
     * @var array
     */
    public const NULLABLE = [
        'nullable',
        'nullableMorphs',
        'nullableUuidMorphs',
        'nullableTimestamps',
        'nullableTimestampsTz',
        'timestamps',
        'timestampsTz',
        'softDeletes',
        'softDeletesTz',
        'rememberToken',
    ];

    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['methods'] = collect();
        $this->attributes['isNullable'] = isset($attributes['name']) && in_array(
            $attributes['name'], static::NULLABLE, true
        );

        parent::__construct($attributes);
    }

    /**
     * Return the name of the column.
     *
     * @return null|string
     */
    public function getName()
    {
        return $this->name ?? static::DEFAULT_NAMES[$this->type] ?? null;
    }

    /**
     * Returns the column name as snake case.
     *
     * @return string
     */
    public function getSnakeName()
    {
        return Str::snake($this->getName());
    }

    /**
     * Returns if the column has been made for a relation.
     *
     * @return bool
     */
    public function isForRelation()
    {
        return $this->relation !== null;
    }

    /**
     * Returns if the Column should be considered as primary key.
     *
     * @return bool
     */
    public function isPrimary()
    {
        return in_array($this->type, Primary::PRIMARY_COLUMN_METHODS, true);
    }

    /**
     * Returns if the Column is a Timestamps declaration.
     *
     * @return bool
     */
    public function isTimestamps()
    {
        return in_array($this->type, Timestamps::TIMESTAMPS, true);
    }

    /**
     * Returns if the Column is a Timestamp.
     *
     * @return bool
     */
    public function isTimestamp()
    {
        return in_array($this->type, ['timestamp', 'timestampTz']);
    }

    /**
     * Returns if the Column is a Timestamps declaration.
     *
     * @return bool
     */
    public function isSoftDeletes()
    {
        return in_array($this->type, SoftDelete::SOFT_DELETES, true);
    }

    /**
     * Returns the PHP type of the column.
     *
     * @return string
     */
    public function phpType()
    {
        return static::getPhpType($this->attributes['type']);
    }

    /**
     * Returns the Eloquent Cast type, if any.
     *
     * @return string
     */
    public function castType()
    {
        if ($this->shouldCastToDate()) {
            return 'datetime';
        }

        foreach (self::BLUEPRINT_TO_CAST as $type => $columnType) {
            if (in_array($this->type, $columnType, true)) {
                return $type;
            }
        }

        return 'string';
    }

    /**
     * Checks if it should be mutated to date.
     *
     * @return bool
     */
    public function shouldCastToDate()
    {
        return in_array($this->type, self::BLUEPRINT_TO_DATES, true);
    }

    /**
     * Returns if the Column should be commented in PHPDoc blocks.
     *
     * @return bool
     */
    public function hidesRealBlueprintMethods()
    {
        return ! $this->isForRelation() && ! in_array($this->attributes['type'], static::UNCOMMENTABLE, true);
    }

    /**
     * Returns if the Column should be fillable inside the model.
     *
     * @return bool
     */
    public function isUnfillable()
    {
        return in_array($this->type, array_merge(static::getUnfillable()), true);
    }

    /**
     * Returns the Column as an string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return Method::methodsToString($this->methods) ?? '';
    }

    /**
     * Return all unfillable columns for the model.
     *
     * @return array
     */
    public static function getUnfillable()
    {
        return array_unique(array_merge(
            ['timestamp', 'timestampsTz', 'boolean', 'uuid'],
            static::UNCOMMENTABLE,
            array_values(static::INCREMENTING_TO_INTEGER),
            array_keys(static::INCREMENTING_TO_INTEGER)
        ));
    }

    /**
     * Returns the PHP variable type for a column type.
     *
     * @param  string  $type
     * @return mixed|string
     */
    public static function getPhpType(string $type)
    {
        foreach (static::BLUEPRINT_TO_TYPE as $name => $phpType) {
            if (in_array($type, $phpType, true)) {
                return $name;
            }
        }

        return 'string';
    }

    /**
     * Guess and creates a Column instance based on a given Relation.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Relation  $relation
     * @param  \Illuminate\Support\Collection  $models
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    public static function guessRelationColumn(Relation $relation, Collection $models)
    {
        /** @var \Larawiz\Larawiz\Lexing\Database\Model $model */
        if (! $model = $models->firstWhere('class', $relation->hasModel)) {
            throw new LogicException("The model for the {$relation->name} doesn't exists.");
        }

        $column = new Column;

        // If the model is using primary column, we will use that information to locate the column.
        if ($model->primary->column) {
            $column->name = Str::snake($model->class) . '_' . $model->primary->column->name;
            $column->type = $model->columns->firstWhere('name', $model->primary->column)->type;
        }
        else {
            $column->name = Str::snake($model->class) . '_' . $relation->name;
            $column->type = $model->columns->firstWhere('name', $relation->name)->type;
        }

        return $column;
    }

    /**
     * Creates a Column instance from a name and line.
     *
     * @param  string  $name
     * @param  null|string  $line
     * @return static
     */
    public static function fromLine(string $name, $line = null)
    {
        // Bail out if the line contains both unique and index declarations, because it's a typo
        if (Str::containsAll($line, ['index', 'unique'])) {
            throw new LogicException("The [{$name}] column must contain either [index] or [unique], not both.");
        }

        $column = new static([
            'name' => $name
        ]);

        // If the name is "id" or "uuid", we will understand that the model wants to use UUID as
        // primary key, and the first argument will be the name. In that case we will swap both
        // values. With this we ensure the developer only sets one UUID as primary key instead.
        if ($line && in_array($name, ['id', 'uuid'])) {
            $column->name = Str::before($line, ' ');
            $column->type = $name;
            $column->methods = Method::parseManyMethods("$name:$column->name");
        }
        // If the column is a Soft Deletes declaration using "softDeletes" or "softDeletesTz", we
        // will understand the developer wants to use soft deletes that may or may not come with
        // a custom column. In that case we will prepare these migration methods automatically.
        elseif (in_array($name, SoftDelete::SOFT_DELETES, true)) {
            $column->name = $line ? Str::before($line, ' ') : SoftDelete::COLUMN;
            $column->type = $name;
            $column->methods = Method::parseManyMethods($name . ($line ? ':' . $line : null));
        }
        // If the line is empty, like "id: ~", then we will assume the developer wants to declare
        // something like "id()". In that case we will just swap the type for the name and add
        // the name as the only method for the Column. That way we will not break anything.
        elseif (empty($line)) {
            $column->type = $name;
            $column->methods = Method::parseManyMethods($name);
        }
        // Then, we will just parse the column as it comes. The column name is basically the name
        // first, the type (with arguments) after, and the rest of Blueprint methods chained in
        // the same line, that also may contain arguments. For that, we need some tricky swap.
        else {
            $column->methods = static::parseNormalizedMethods($name, $line);
            $column->name = $name;
            $column->type = $column->methods->first()->name;
        }

        $column->isNullable = Str::contains($line, 'nullable');

        return $column;
    }

    /**
     * Parses methods from a column definition but normalized.
     *
     * @param  string  $name
     * @param  string  $line
     * @return \Larawiz\Larawiz\Lexing\Code\Method[]|\Illuminate\Support\Collection
     */
    protected static function parseNormalizedMethods(string $name, string $line)
    {
        $calls = explode(' ', $line);

        $first = Str::of($calls[0]);

        // If the column syntax is for a relation, or its malformed, we will just throw an exception
        // because the developer may have a typo on the declaration. At the same time, we'll remind
        // him no relation are allowed in columns (only for models) and the correct syntax to use.
        if ($first->startsWith(array_keys(BaseRelation::RELATION_CLASSES))) {
            throw new LogicException("Relations are not allowed in the [{$name}] column definition.");
        }

        if ($first->contains(',') && ! $first->contains(':')) {
            throw new LogicException("The [{$name}] column syntax malformed. Use [name: type:argument,argument...].");
        }

        // To transform the column declaration into a method collection we can simply
        // take the name as the first argument of the method call and append if to
        // if using "type:name" or "type:name,foo" declaration and then return.
        if ($first->contains(':')) {
            $calls[0] = $first->replaceFirst(':', ':' . $name . ',')->trim(',')->__toString();
        } else {
            $calls[0] .= ':' . $name;
        }

        return Method::parseManyMethods(implode(' ', $calls));
    }

    /**
     * Returns the real method for an incrementing key.
     *
     * @return string
     */
    public function realMethod()
    {
        return Arr::get(static::INCREMENTING_TO_INTEGER, $this->type, $this->type);
    }
}
