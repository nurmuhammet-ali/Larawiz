<?php

namespace Larawiz\Larawiz\Lexing;

use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use Illuminate\Config\Repository;

/**
 * Class ScaffoldDatabase
 *
 * @package Larawiz\Larawiz\Parser
 *
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Model[] $models
 * @property \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Migration[] $migrations
 */
class ScaffoldDatabase extends Fluent
{
    /**
     * Sets the namespace for the Scaffold Database models.
     *
     * @param  null|string  $namespace
     * @return string
     */
    public function setNamespace(?string $namespace)
    {
        $namespace = Str::of($namespace)->start(app()->getNamespace())->finish('\\')->beforeLast('\\')->__toString();

        return $this->namespace = $namespace;
    }

    /**
     * Creates a new Scaffold Database.
     *
     * @return static
     */
    public static function make()
    {
        return new static([
            'models'     => collect(),
            'migrations' => collect(),
        ]);
    }
}
