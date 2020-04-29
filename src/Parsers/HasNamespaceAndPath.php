<?php

namespace Larawiz\Larawiz\Parsers;

use Illuminate\Support\Str;

/**
 * @property string $path
 * @property string $namespace
 * @property string $relativeNamespace
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
}
