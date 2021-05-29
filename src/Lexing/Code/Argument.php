<?php

namespace Larawiz\Larawiz\Lexing\Code;

use Illuminate\Support\Fluent;
use Illuminate\Support\Str;

/**
 * Class Argument
 *
 * @package Larawiz\Larawiz\Lexing
 *
 * @property string $value
 * @property string $type
 * @property null|string $property
 */
class Argument extends Fluent
{
    /**
     * Types of arguments.
     *
     * @var array
     */
    public const TYPES = [
        'string',
        'bool',
        'numeric',
        'class',
        'variable',
        'null',
    ];

    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * Checks if the argument is just a string.
     *
     * @return bool
     */
    public function isString()
    {
        return $this->type === 'string';
    }

    /**
     * Returns if the Argument is a Class name.
     *
     * @return bool
     */
    public function isClass()
    {
        return $this->type === 'class';
    }

    /**
     * Returns if the Argument is a boolean.
     *
     * @return bool
     */
    public function isBool()
    {
        return $this->type === 'bool';
    }

    /**
     * Returns if the Argument is an integer.
     *
     * @return bool
     */
    public function isNumeric()
    {
        return $this->type === 'numeric';
    }

    /**
     * Returns if the Argument is a Class name.
     *
     * @return bool
     */
    public function isVariable()
    {
        return $this->type === 'variable';
    }

    /**
     * Returns if the Argument is an empty string.
     *
     * @return bool
     */
    protected function isEmpty()
    {
        return $this->type === 'empty';
    }

    /**
     * Returns if the Argument is a Class name.
     *
     * @return bool
     */
    public function isNull()
    {
        return $this->type === 'null';
    }


    /**
     * Creates an Argument from a string
     *
     * @param  string  $argument
     * @return static
     */
    public static function from(string $argument)
    {
        if ($argument === '' || $argument === '~') {
            return new static([
                'value' => null,
                'type' => 'empty'
            ]);
        }

        if (strtolower($argument) === 'null') {
            return new static([
                'value' => null,
                'type' => 'null'
            ]);
        }

        if (ctype_upper($argument[0])) {
            return new static([
                'value' => $argument,
                'type' => 'class'
            ]);
        }

        if (in_array(strtolower($argument), ['true', 'false'], true)) {
            return new static([
                'value' => strtolower($argument),
                'type' => 'bool',
            ]);
        }

        if (is_numeric($argument)) {
            return new static([
                'value' => +$argument,
                'type' => 'numeric',
            ]);
        }

        if (Str::contains($argument, '.')) {
            return new static([
                'value' => $argument,
                'type' => 'variable',
            ]);
        }

        return new static([
            'value' => $argument,
            'type' => 'string'
        ]);
    }

    /**
     * Returns the Argument as a string.
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->isClass()) {
            return "{$this->value}::class";
        }

        if ($this->isString()) {
            return "'{$this->value}'";
        }

        if ($this->isBool() || $this->isNumeric()) {
            return $this->value;
        }

        if ($this->isNull()) {
            return 'null';
        }

        if ($this->isEmpty()) {
            return '';
        }

        return "\${$this->value}" . ($this->property ? "->{$this->property}" : '');
    }
}
