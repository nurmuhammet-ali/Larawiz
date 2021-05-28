<?php
/**
 * This class allows to centralize the raw data parsed and the scaffold information from each
 */

namespace Larawiz\Larawiz;

use Illuminate\Config\Repository;
use Illuminate\Support\Fluent;
use Larawiz\Larawiz\Lexing\ScaffoldAuth;
use Larawiz\Larawiz\Lexing\ScaffoldDatabase;
use Larawiz\Larawiz\Lexing\ScaffoldHttp;

/**
 * Class Scaffold
 *
 * @package Larawiz\Larawiz
 *
 * @property \Illuminate\Config\Repository $rawDatabase
 *
 * @property \Larawiz\Larawiz\Lexing\ScaffoldDatabase $database
 */
class Scaffold extends Fluent
{
    /**
     * Creates a new Scaffold.
     *
     * @return static
     */
    public static function make()
    {
        return new static([
            'rawDatabase' => new Repository,
            'rawHttp' => new Repository,
            'rawAuth' => new Repository,
            'database' => ScaffoldDatabase::make(),
        ]);
    }

    /**
     * Returns a given model from the raw database scaffold, or one of its given keys.
     *
     * @param  string  $key
     * @param  string|null  $sub
     * @return array
     */
    public function getRawModel(string $key, string $sub = null)
    {
        $key = $sub ? "$key.$sub" : $key;

        return $this->rawDatabase->get("models.{$key}");
    }
}
