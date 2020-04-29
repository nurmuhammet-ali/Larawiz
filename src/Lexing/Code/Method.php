<?php

namespace Larawiz\Larawiz\Lexing\Code;

use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Fluent;
use Illuminate\Support\Collection;

/**
 * Class Method
 *
 * @package Larawiz\Larawiz\Parser
 *
 * @property string $name
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Argument[] $arguments
 */
class Method extends Fluent
{
    /**
     * Method constructor.
     *
     * @param  array  $attributes
     */
    public function __construct($attributes = [])
    {
        $this->attributes['arguments'] = collect();

        parent::__construct($attributes);
    }

    /**
     * Returns the method call as a string.
     *
     * @return string
     */
    public function __toString()
    {
        $string = $this->name . '(';

        if ($this->arguments->isNotEmpty()) {
            $string .= implode(', ', $this->arguments->map->__toString()->toArray());
        }

        return $string . ')';
    }

    /**
     * Parses a Method line.
     *
     * @param  string  $method
     * @return \Larawiz\Larawiz\Lexing\Code\Method
     */
    public static function parseMethod(string $method)
    {
        // If the method has no arguments, we will just create an instance with empty arguments.
        if (! Str::contains($method, ':')) {
            return new static([
                'name' => $method
            ]);
        }

        $arguments = explode(',', Str::after($method, ':'));

        foreach ($arguments as $key => $argument) {
            $arguments[$key] = Argument::from($argument);
        }

        return new static([
            'name' => Str::before($method, ':'),
            'arguments' => collect($arguments)
        ]);
    }

    /**
     * Parses a collection of methods to a string.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[]  $methods
     * @return null|string
     */
    public static function methodsToString(Collection $methods)
    {
        if ($methods->isNotEmpty()) {
            return implode('->', $methods->map->__toString()->toArray());
        }

        return null;
    }

    /**
     * Returns a collection of methods from a line.
     *
     * @param  string  $line
     * @return \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[]
     */
    public static function parseManyMethods($line)
    {
        $collection = collect();

        foreach (explode(' ', trim($line)) as $method) {
            $collection->push(static::parseMethod($method));
        }

        return $collection;
    }
}
