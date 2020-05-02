<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Faker\Generator;
use ReflectionMethod;
use Illuminate\Support\Str;

class Factory
{
    /**
     * Name of the soft-deleted state.
     *
     * @var string
     */
    public const SOFT_DELETED_STATE = 'deleted';

    /**
     * Saved password hash for all models.
     *
     * Using this we avoid generating the password for each model every time.
     *
     * @var string
     */
    protected $secretPassword;

    /**
     * A cached list of formatters.
     *
     * @var
     */
    protected $formatters;

    /**
     * Faker Generator.
     *
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * Factory constructor.
     *
     * @param  \Faker\Generator  $faker
     */
    public function __construct(Generator $faker)
    {
        $this->faker = $faker;
    }

    /**
     * Guesses which type or method it should call from Faker for the factory attributes.
     *
     * @param  string  $name
     * @param  string  $type
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException|\ReflectionException
     */
    public function guess(string $name, string $type)
    {
        // If it's a password, we return the password string.
        if ($name === 'password' && $type === 'string') {
            return "'" . $this->returnPassword() . "'";
        }

        if ($formatter = $this->getFakerFormatter(Str::camel($name))) {
            return $formatter;
        }

        switch ($type) {
            case 'boolean':
                return '$faker->boolean';
            case 'uuid':
                return '$faker->uuid';
            case 'date':
                return '$faker->date';
            case 'dateTime':
            case 'dateTimeTz':
            return '$faker->dateTime';
            case 'time':
            case 'timeTz':
            return '$faker->time';
            case 'year':
                return '$faker->year';
            case 'text':
            case 'mediumText':
            case 'longText':
                return '$faker->realText()';
            case 'integer':
            case 'unsignedInteger':
            case 'unsignedTinyInteger':
            case 'unsignedSmallInteger':
            case 'unsignedMediumInteger':
            case 'unsignedBigInteger':
                return '$faker->randomNumber()';
            case 'ipAddress':
                return '$faker->ipv4';
            case 'macAddress':
                return '$faker->macAddress';
            case 'float':
            case 'double':
            case 'decimal':
            case 'unsignedFloat':
            case 'unsignedDouble':
            case 'unsignedDecimal':
                return '$faker->randomFloat()';
        }

        return '$faker->' . $name;
    }

    /**
     * Returns a password string.
     *
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function returnPassword()
    {
        // To avoid creating the password every time, we will just
        return $this->secretPassword = $this->secretPassword ?? app('hash')->make('secret');
    }

    /**
     * Return the faker formatter string if it exists.
     *
     * @param  string  $formatter
     * @return string|void
     * @throws \ReflectionException
     */
    protected function getFakerFormatter(string $formatter)
    {
        // First we are gonna check if the formatter was already checked as valid.
        // If it is not, we are gonna cycle through each Faker providers to check
        // if the formatter exists and return the string the Factory should use.
        if (isset($this->formatters[$formatter])) {
            return $this->formatters[$formatter];
        }

        foreach ($this->faker->getProviders() as $provider) {
            if (method_exists($provider, $formatter)) {
                $parameters = (new ReflectionMethod($provider, $formatter))->getNumberOfParameters();

                return $this->formatters[$formatter] = '$faker->' . $formatter . ($parameters ? '()' : '');
            }
        }
    }
}
