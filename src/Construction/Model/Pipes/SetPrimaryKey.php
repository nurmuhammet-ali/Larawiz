<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Nette\PhpGenerator\ClassType;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetPrimaryKey
{
    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->isPivot()) {
            $this->pivotMayEnablePrimary($construction->model, $construction->class);
        } elseif (! $construction->model->primary->using) {
            $this->modelDisablePrimary($construction->class);
        } elseif (! $construction->model->primary->isDefault()) {
            $this->modelEnableCustomPrimary($construction->model, $construction->class);
        }

        return $next($construction);
    }

    /**
     * Enables the Primary Key for the Pivot Model because it has been set manually.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Nette\PhpGenerator\ClassType  $class
     * @return void
     */
    protected function pivotMayEnablePrimary(Model $model, ClassType $class)
    {
        if (! $model->primary->using) {
            if ($model->primary->column) {
                $model->columns->pull($model->primary->column->name);
            }
            return;
        }

        if ('id' !== $name = $model->primary->column->getName()) {
            $this->setPrimaryKeyProperty($class, $name);
        }

        if ($model->primary->column->isPrimary()) {
            $this->setIncrementingProperty($class, true);
        }

        if ('int' !== $type = $model->primary->column->castType()) {
            $this->setTypeProperty($class, $type);
        }
    }

    /**
     * Unset the Primary Key information for the Model.
     *
     * @param  \Nette\PhpGenerator\ClassType  $class
     */
    protected function modelDisablePrimary(ClassType $class)
    {
        // To unset the primary key we only need to disable the property and the incrementing.
        $this->setPrimaryKeyProperty($class, null);
        $this->setIncrementingProperty($class, false);
    }

    /**
     * Sets a non-default primary key for the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Nette\PhpGenerator\ClassType  $class
     */
    protected function modelEnableCustomPrimary(Model $model, ClassType $class)
    {
        if ('id' !== $name = $model->primary->column->getName()) {
            $this->setPrimaryKeyProperty($class, $name);
        }

        if (! $model->primary->column->isPrimary()) {
            $this->setIncrementingProperty($class, false);
        }

        if ('int' !== $type = $model->primary->column->castType()) {
            $this->setTypeProperty($class, $type);
        }
    }

    /**
     * Sets the primary key property value.
     *
     * @param  \Nette\PhpGenerator\ClassType  $class
     * @param  null|string  $name
     */
    protected function setPrimaryKeyProperty(ClassType $class, ?string $name = 'id')
    {
        $property = $class->addProperty('primaryKey')
            ->setProtected()
            ->addComment('The primary key for the model.')
            ->addComment('')
            ->addComment('@var string');

        $name ? $property->setValue($name) : $property->setNullable()->setInitialized();
    }

    /**
     * Sets the incrementing property.
     *
     * @param  \Nette\PhpGenerator\ClassType  $class
     * @param  bool  $incrementing
     */
    protected function setIncrementingProperty(ClassType $class, bool $incrementing)
    {
        $class->addProperty('incrementing', $incrementing)
            ->setPublic()
            ->addComment('Indicates if the IDs are auto-incrementing.')
            ->addComment('')
            ->addComment('@var bool');
    }

    /**
     * Sets the type of the primary key.
     *
     * @param  \Nette\PhpGenerator\ClassType  $class
     * @param  string  $type
     */
    protected function setTypeProperty(ClassType $class, string $type = 'int')
    {
        $class->addProperty('keyType', $type)
            ->setProtected()
            ->addComment('The "type" of the primary key ID.')
            ->addComment('')
            ->addComment('@var string');
    }
}
