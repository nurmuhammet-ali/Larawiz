<?php

namespace Larawiz\Larawiz;

use LogicException;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Model;
use const DIRECTORY_SEPARATOR;

class Helpers
{
    /**
     * Guesses the model name from the relation name.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Model[]  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @return string
     */
    public static function guessModelFromRelationName(Collection $models, Model $model, string $name)
    {
        $modelName = Str::of($name)->singular()->studly()->__toString();

        $parent = $models->firstWhere('class', $modelName);

        if ($parent) {
            return $parent->key;
        }

        throw new LogicException("The [{$name}] relation of [{$model->key}] must have a target model.");
    }

    /**
     * Guess the target model and through model from a "hasOneThrough" or "hasManyThrough".
     *
     * @param  \Illuminate\Support\Collection  $models
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  string  $name
     * @return string
     */
    public static function guessModelsFromRelationName(Collection $models, Model $model, string $name)
    {
        $guessed = Str::of($name)->snake('_')->explode('_')->map(function ($model) {
                return Str::of($model)->singular()->studly()->__toString();
            })->slice(-2, 2);

        if ($guessed->count() < 1) {
            throw new LogicException("The [{$name}] relation of [{$model->key}] must be set as {throughModel}.");
        }

        $models = $models->filter(function (Model $model) use ($guessed) {
            return in_array($model->class, $guessed->all(), true);
        });

        if ($models->count() < 1) {
            throw new LogicException("The [{$name}] relation of [{$model->key}] contains non-existent models.");
        }

        return $models->map->key->reverse()->implode(',');
    }


    /**
     * Returns an array with the class namespace and the class name.
     *
     * @param  string  $fullNamespace
     * @param \Illuminate\Contracts\Foundation\Application|string $app
     * @return array
     */
    public static function namespaceAndClass(string $fullNamespace, $app)
    {
        $base = is_string($app) ? $app : $app->getNamespace();

        if (Str::contains($fullNamespace, '\\')) {
            $base .= '\\' . Str::beforeLast($fullNamespace, '\\');
        }

        return [ $base, Str::afterLast($fullNamespace, '\\') ];
    }

    /**
     * Returns the full path for a given full namespace.
     *
     * @param  string  $fullNamespace
     * @param  \Illuminate\Contracts\Foundation\Application|string  $app
     * @param  string  $appNamespace
     * @return string
     */
    public static function pathFromNamespace(string $fullNamespace, $app, string $appNamespace)
    {
        $base = is_string($app) ? $app : $app->path();

        $fullNamespace = trim(Str::after($fullNamespace, $appNamespace), '\\');

        return implode(DIRECTORY_SEPARATOR, [
            $base,
            str_replace('\\', DIRECTORY_SEPARATOR, $fullNamespace) . '.php'
        ]);
    }

    /**
     * Returns the directory from a full file path.
     *
     * @param  string  $path
     * @return string
     */
    public static function directoryFromPath(string $path)
    {
        return Str::beforeLast($path, DIRECTORY_SEPARATOR);
    }
}
