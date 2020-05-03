<?php

namespace Larawiz\Larawiz\Lexing;

use Illuminate\Support\Fluent;

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
