<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Helpers;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Factory;
use Illuminate\Contracts\Foundation\Application;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use const DIRECTORY_SEPARATOR as DS;

class WriteFactory
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Application Filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Larawiz\Larawiz\Lexing\Database\Factory
     */
    protected $factory;

    /**
     * Creates a new WriteRepository instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  \Larawiz\Larawiz\Lexing\Database\Factory  $factory
     */
    public function __construct(Application $app, Filesystem $filesystem, Factory $factory)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;

        $this->factory = $this->registerFactory($factory);
    }

    /**
     * Registers the factory instance as a singleton at runtime.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Factory  $factory
     * @return \Larawiz\Larawiz\Lexing\Database\Factory
     */
    protected function registerFactory(Factory $factory)
    {
        $this->app->instance(Factory::class, $factory);

        return $factory;
    }

    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->useFactory) {

            $path = $this->factoryPath($construction->model);

            $this->filesystem->ensureDirectoryExists(Helpers::directoryFromPath($path), true);

            $this->filesystem->put($path, $this->createFile($construction->model));
        }

        return $next($construction);
    }

    /**
     * Returns the path for the Factory file.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return string
     */
    protected function factoryPath(Model $model)
    {
        return $this->app->databasePath('factories' . DS . $model->class . 'Factory.php');
    }

    /**
     * Creates the Factory file contents
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    protected function createFile(Model $model)
    {
        $string = '<?php'
                . "\n"
                . "\nuse {$model->fullNamespace()};"
                . "\nuse Faker\Generator as Faker;"
                . "\n"
                . "\n// TODO: Review the Factory for the {$model->class} model."
                . "\n"
                . "\n/** @var \Illuminate\Database\Eloquent\FactoryBuilder \$factory */"
                . "\n"
                . "\n\$factory->define({$model->class}::class, function (Faker \$faker) {"
                . "\n    return [";

        $fillable = $this->getAllFillableColumns($model->columns);

        if ($fillable->isEmpty()) {
            $string .= "\n        // ...";
        } else {
            foreach ($fillable as $name => $column) {
                $string .= $this->getPropertyString($column);
            }
        }

        $string .= "\n    ];"
                .  "\n});"
                .  "\n";

        if ($model->softDelete->using) {
            $string .= $this->addSoftDeletedState($model);
        }

        foreach ($model->factoryStates as $state) {
            $string .= "\n\$factory->state({$model->class}::class, '{$state}', function (Faker \$faker) {"
                     . "\n    return ["
                     . "\n        // TODO: Add attributes for the {$model->key} \"{$state}\" state."
                     . "\n    ];"
                     . "\n});"
                     . "\n";
        }

        return $string;
    }

    /**
     * Get all fillable columns for the model.
     *
     * @param  \Illuminate\Support\Collection  $columns
     * @return \Illuminate\Support\Collection
     */
    protected function getAllFillableColumns(Collection $columns)
    {
        return $columns->filter(function (Column $column) {
            return $this->columnShouldBeFilledInFactory($column);
        });
    }

    /**
     * Return the string for the model property in the factory.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return string|void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    protected function getPropertyString(Column $column)
    {
        if ($this->columnShouldBeFilledInFactory($column)) {
            return "\n        '{$column->name}' => " . $this->factory->guess($column->name, $column->type). ',';
        }
    }

    /**
     * Returns if the column should be filled by the factory.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    public function columnShouldBeFilledInFactory(Column $column)
    {
        return ! $column->isPrimary()
            && ! $column->isTimestamps()
            && ! $column->isTimestamp()
            && ! $column->isSoftDeletes()
            && ! $column->isForRelation()
            && ! $column->isNullable();
    }

    /**
     * Adds a Soft Deleted state to the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return string
     */
    protected function addSoftDeletedState(Model $model)
    {
        return "\n\$factory->state({$model->class}::class, '" . Factory::SOFT_DELETED_STATE . "', function (Faker \$faker) {"
            . "\n    return ["
            . "\n        '{$model->softDelete->column}' => \$faker->dateTime,"
            . "\n    ];"
            . "\n});"
            . "\n";
    }
}
