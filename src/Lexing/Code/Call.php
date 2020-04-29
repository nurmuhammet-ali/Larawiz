<?php

namespace Larawiz\Larawiz\Lexing\Code;

use Illuminate\Support\Str;
use Illuminate\Support\Fluent;

/**
 * Class Call
 *
 * @package Larawiz\Larawiz\Lexing\Code
 *
 * @property null|string $call
 * @property string $type
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Argument[] $arguments
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Code\Method[] $methods
 */
class Call extends Fluent
{
    /**
     * Types of calls for the method instance.
     *
     * @var array
     */
    public const TYPES = [
        'method',   // "{call}::{methods}"
        'function', // "{methods}";
        'variable'  // ${call}->{method}{arguments}
    ];

    /**
     * Create a new fluent instance.
     *
     * @param  array|object  $attributes
     * @return void
     */
    public function __construct($attributes = [])
    {
        $this->attributes['type'] = 'function';

        parent::__construct($attributes);
    }

    /**
     * Checks if the Call is for a class method.
     *
     * @return bool
     */
    public function isMethod()
    {
        return $this->attributes['type'] === 'method';
    }

    /**
     * Checks if the Call is for a global function.
     *
     * @return bool
     */
    public function isFunction()
    {
        return $this->attributes['type'] === 'function';
    }

    /**
     * Checks if the Call is for a object instance method in a variable.
     *
     * @return bool
     */
    public function isVariable()
    {
        return $this->attributes['type'] === 'variable';
    }

    /**
     * Returns a string representation of this class instance.
     *
     * @return string
     */
    public function __toString()
    {
        $methods = $this->methods->isEmpty() ?: Method::methodsToString($this->methods);

        if ($this->isFunction()) {
            return $methods;
        }

        if ($this->isMethod()) {
            return $this->call . '::' . $methods;
        }

        return '$' . $this->call . (empty($methods) ? '' : '->' . $methods);
    }

}
