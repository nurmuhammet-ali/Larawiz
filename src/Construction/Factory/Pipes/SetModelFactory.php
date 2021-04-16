<?php

namespace Larawiz\Larawiz\Construction\Factory\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Construction\Factory\FactoryConstruction;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Factory as LarawizFactory;
use Larawiz\Larawiz\Lexing\Database\Model;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpFile;

class SetModelFactory
{
    /**
     * Name of the soft-deleted state.
     *
     * @var string
     */
    public const SOFT_DELETED_STATE = 'deleted';

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
    public function __construct(Application $app, Filesystem $filesystem, LarawizFactory $factory)
    {
        $this->app = $app;
        $this->filesystem = $filesystem;
        $this->factory = $this->registerFactory($factory);
    }

    /**
     * Registers the factory instance as a singleton at runtime.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Factory  $factory
     *
     * @return \Larawiz\Larawiz\Lexing\Database\Factory
     */
    protected function registerFactory(LarawizFactory $factory)
    {
        $this->app->instance(LarawizFactory::class, $factory);

        return $factory;
    }

    /**
     * Handle the factory construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Factory\FactoryConstruction  $construction
     * @param  \Closure  $next
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function handle(FactoryConstruction $construction, Closure $next)
    {
        $construction->file = new PhpFile();

        $construction->file->addUse(Factory::class);
        $construction->file->addUse($construction->model->fullNamespace());
        $namespace = $construction->file->addNamespace('Database\Factories');


        $construction->class = $construction->file->addClass(Str::finish($construction->model->class, 'Factory'));

        $namespace->addClass($construction->class);

        $construction->class->setExtends(Factory::class);

        $construction->class
            ->addProperty('model', new Literal($construction->model->class . '::class'))
            ->setProtected()
            ->addComment("The name of the factory's corresponding model.")
            ->addComment('')
            ->addComment('@var string');

        $construction->class->addMethod('definition')
            ->setPublic()
            ->addComment("Define the model's default state")
            ->addComment('')
            ->addComment('@return array')
            ->setBody($this->setDefinitionBody($construction->model));

        return $next($construction);
    }

    /**
     * Returns the definition code as a string.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     *
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    protected function setDefinitionBody(Model $model)
    {
        $string = "\nreturn [";

        $fillable = $this->getAllFillableColumns($model->columns);

        if ($fillable->isEmpty()) {
            $string .= "\n    // ...";
        } else {
            foreach ($fillable as $name => $column) {
                $string .= $this->getPropertyString($column);
            }
        }

        $string .= "\n];";

        return $string;
    }

    /**
     * Get all fillable columns for the model.
     *
     * @param  \Illuminate\Support\Collection  $columns
     *
     * @return \Illuminate\Support\Collection
     */
    protected function getAllFillableColumns(Collection $columns)
    {
        return $columns->filter(
            function (Column $column) {
                return $this->columnShouldBeFilledInFactory($column);
            }
        );
    }

    /**
     * Return the string for the model property in the factory.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     *
     * @return string|void
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    protected function getPropertyString(Column $column)
    {
        if ($this->columnShouldBeFilledInFactory($column)) {
            return "\n    '{$column->name}' => " . $this->factory->guess($column->name, $column->type) . ',';
        }
    }

    /**
     * Returns if the column should be filled by the factory.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     *
     * @return bool
     */
    public function columnShouldBeFilledInFactory(Column $column)
    {
        return !$column->isPrimary()
            && !$column->isTimestamps()
            && !$column->isTimestamp()
            && !$column->isSoftDeletes()
            && !$column->isForRelation()
            && !$column->isNullable();
    }
}
