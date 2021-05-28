<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Fluent;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Lexing\HasNamespaceAndPath;

/**
 * Class Model
 *
 * @package Larawiz\Larawiz\Parser
 *
 * @property string $key The key name for the collection.
 *
 * @property string $modelType  The parent class type, that could be "Model", "User", "Pivot" or "MorphPivot".
 * @property int $perPage  How many models per page should be set.
 * @property bool $seeder  If a seeder should be created
 *
 * @property null|string $table  The table name, in case it's not the default.
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[] $columns
 * @property \Illuminate\Support\Collection|string[] $fillable
 * @property \Illuminate\Support\Collection|string[] $hidden
 * @property \Illuminate\Support\Collection|string[] $append
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Relations\BaseRelation[] $relations
 *
 * @property \Illuminate\Support\Collection|string[] $eager  Eager Loads.
 *
 * @property \Larawiz\Larawiz\Lexing\Database\Primary $primary  Primary column information
 * @property \Larawiz\Larawiz\Lexing\Database\Timestamps $timestamps  Timestamps information.
 * @property \Larawiz\Larawiz\Lexing\Database\SoftDelete $softDelete  Soft Deleting information.
 *
 * @property null|string $routeBinding  Column that should be bound by default, it any.
 * @property bool $useFactory  If a factory should be created for it.
 * @property \Illuminate\Support\Collection|string[] $factoryStates  States for the factory.
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\QuickCast[] $quickCasts
 *
 * @property \Illuminate\Support\Collection|string[] $globalScopes
 * @property \Illuminate\Support\Collection|string[] $localScopes
 * @property bool $observer
 *
 * @property \Larawiz\Larawiz\Lexing\Database\Migration $migration
 *
 * @property bool $is_cast_enabled
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\QuickTrait[] $quickTraits
 */
class Model extends Fluent
{
    use HasNamespaceAndPath;

    /**
     * Model types.
     *
     * @var array
     */
    public const MODEL_TYPE_MAP = [
        'user'       => \Illuminate\Foundation\Auth\User::class,
        'model'      => \Illuminate\Database\Eloquent\Model::class,
        'pivot'      => \Illuminate\Database\Eloquent\Relations\Pivot::class,
        'morphPivot' => \Illuminate\Database\Eloquent\Relations\MorphPivot::class,
    ];

    /**
     * Default number of models to retrieve for a page.
     *
     * @var int
     */
    public const MODEL_PER_PAGE = 15;

    /**
     * Returns if the Model is a Pivot model.
     *
     * @return bool
     */
    public function isPivot()
    {
        return in_array($this->modelType, [
            'pivot'      => Pivot::class,
            'morphPivot' => MorphPivot::class,
        ], true);
    }

    /**
     * Returns the snake case singular name of the model.
     *
     * @return string
     */
    public function singular()
    {
        return Str::snake($this->class);
    }

    /**
     * Returns the lowercase version of the class name for comparison.
     *
     * @return string
     */
    public function lowercase()
    {
        return Str::lower($this->class);
    }

    /**
     * Returns if the model uses a non default per-page value.
     *
     * @return bool
     */
    public function usesNonDefaultPerPage()
    {
        return $this->perPage !== static::MODEL_PER_PAGE;
    }

    /**
     * Returns if it's using a custom table name.
     *
     * @return bool
     */
    public function usesNonDefaultTable()
    {
        return $this->table !== $this->getPluralTableName();
    }

    /**
     * Guesses the table for the given Model.
     *
     * @return string
     */
    public function getTableName()
    {
        if ($this->table) {
            return $this->table;
        }

        return $this->modelType === Pivot::class
                ? $this->getSingularTableName()
                : $this->getPluralTableName();
    }

    /**
     * Returns the table name as plural (for pivot models)
     *
     * @return string
     */
    public function getPluralTableName()
    {
        return Str::snake(Str::pluralStudly($this->class));
    }

    /**
     * Returns the table name as snake (for pivot models)
     *
     * @return string
     */
    public function getSingularTableName()
    {
        return Str::snake(Str::singular($this->class));
    }

    /**
     * Returns if the current model is an User.
     *
     * @return bool
     */
    public function isUser()
    {
        return $this->modelType === \Illuminate\Foundation\Auth\User::class;
    }

    /**
     * Checks if the current model is using an auto-incrementing primary key (like "id").
     *
     * @return bool
     */
    public function hasAutoIncrementPrimaryKey()
    {
        return $this->primary->using && $this->primary->column->type === 'id';
    }

    /**
     * Checks if the current model is using an UUID as primary key.
     *
     * @return bool
     */
    public function hasUuidPrimaryKey()
    {
        return $this->primary->using && $this->primary->column->type === 'uuid';
    }

    /**
     * Create a new Model instance.
     *
     * @param  array  $attributes
     * @return static
     */
    public static function make(array $attributes = [])
    {
        return new static(array_merge([
            'modelType'       => static::MODEL_TYPE_MAP['model'],
            'perPage'         => static::MODEL_PER_PAGE,
            'columns'         => collect(),
            'fillable'        => collect(),
            'hidden'          => collect(),
            'append'          => collect(),
            'relations'       => collect(),
            'eager'           => collect(),
            'primary'         => new Primary,
            'timestamps'      => new Timestamps,
            'softDelete'      => new SoftDelete,
            'observer'        => false,
            'routeBinding'    => null,
            'useFactory'      => true,
            'factoryStates'   => collect(),
            'seeder'          => true,
            'globalScopes'    => collect(),
            'localScopes'     => collect(),
            'quickTraits'     => collect(),
            'is_cast_enabled' => true,
            'quickCasts'      => collect(),
        ], $attributes));
    }
}
