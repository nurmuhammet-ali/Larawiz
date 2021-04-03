<?php

namespace Larawiz\Larawiz\Lexing;

use Illuminate\Support\Str;

/**
 * @property string $path
 * @property string $namespace
 * @property string $relativeNamespace // The final namespace without the application namespace
 * @property string $class
 */
trait HasNamespaceAndPath
{
    /**
     * Returns the directory where the file should be put.
     *
     * @return string
     */
    public function directory()
    {
        return Str::beforeLast($this->path, DIRECTORY_SEPARATOR);
    }

    /**
     * Return the full namespace for the class.
     *
     * @return string
     */
    public function fullNamespace()
    {
        return implode('\\', [$this->namespace, $this->class]);
    }

    /**
     * Returns the full namespace for the class, but rooted.
     *
     * @return string
     */
    public function fullRootNamespace()
    {
        return '\\' . $this->fullNamespace();
    }

    /**
     * Returns the full rooted namespace with the array indicator.
     *
     * @return string
     */
    public function fullRootNamespaceArray()
    {
        return $this->fullRootNamespace() . '[]';
    }

    /**
     * Returns the relative namespace without the proceeding "Model" namespace.
     *
     * @return string
     */
    public function getRelativeNamespaceWithoutModel()
    {
        return Str::after($this->relativeNamespace, 'Models\\');
    }
}
