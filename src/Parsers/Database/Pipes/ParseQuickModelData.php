<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use LogicException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Larawiz\Larawiz\Lexing\Database\Column;

class ParseQuickModelData
{
    /**
     * Handle the parsing of the Database scaffold.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        foreach ($scaffold->rawDatabase->get('models') as $key => $model) {

            if (! is_array($model) || ! Arr::isAssoc($model)) {
                $class = is_int($key) ? $model : $key;
                throw new LogicException("Ensure the [{$class}] is a model declaration.");
            }

            if ($this->isQuickModel($model)) {
                $scaffold->rawDatabase->set('models.' . $key, $this->quickToCustomModel($model));
            }
        }

        return $next($scaffold);
    }

    /**
     * Checks if the model data is for a Quick Model.
     *
     * @param  array  $data
     * @return bool
     */
    protected function isQuickModel(array $data)
    {
        return ! is_array(Arr::get($data, 'columns'));
    }

    /**
     * Completes the Model with the given data plus some default ones.
     *
     * @param  array  $data
     * @return array
     */
    protected function quickToCustomModel(array $data)
    {
        $this->relocateColumns($data);
        $this->relocateTraits($data);
        $this->relocateFactory($data);

        $this->setPrimaryColumn($data);
        $this->setTimestamps($data);
        $this->setHiddenColumns($data);

        $this->maySetUserType($data);

        return $data;
    }

    /**
     * Relocate the columns to the proper array key.
     *
     * @param  array  $data
     * @return void
     */
    protected function relocateColumns(array &$data)
    {
        $data = ['columns' => $data];
    }

    /**
     * Relocate the traits, if there are present.
     *
     * @param  array  $data
     * @return void
     */
    protected function relocateTraits(array &$data)
    {
        if (is_array(Arr::get($data, 'columns.traits'))) {
            $data['traits'] = Arr::pull($data, 'columns.traits');
        }
    }

    /**
     * Relocate the Factory property of the quick model.
     *
     * @param  array  $data
     */
    protected function relocateFactory(array &$data)
    {
        $factory = Arr::get($data, 'columns.factory');

        if ($factory !== null && ! is_string($factory)) {
            $data['factory'] = Arr::pull($data, 'columns.factory');
        }
    }

    /**
     * Sets the primary column as ID or UUID.
     *
     * @param  array  $data
     * @return void
     */
    protected function setPrimaryColumn(array &$data)
    {
        // If the user is using an 'uuid', we will set the primary column to its name.
        if (Arr::has($data, 'columns.uuid')) {
            $data['primary'] = 'uuid';
        }
        // Otherwise we will check if it doesn't have an ID and prepend it automatically.
        elseif (! Arr::has($data, 'columns.id')) {
            $data['columns'] = Arr::prepend($data['columns'], null, 'id');
            // We can use this internal data to keep track of the added id.
            Arr::set($data, 'quick.shouldDeleteId', true);
        }
    }

    /**
     * Add default timestamps columns.
     *
     * @param  array  $data
     * @return void
     */
    protected function setTimestamps(array &$data)
    {
        // If the declaration doesn't have timezone timestamps, add the default timestamps.
        if (! Arr::has($data, ['columns.timestampTz', 'columns.timestamps'])) {
            $data['columns']['timestamps'] = null;
        }
    }

    /**
     * Change the Model type to 'user' if it's necessary.
     *
     * @param  array  $data
     * @return void
     */
    protected function maySetUserType(array &$data)
    {
        // If the model has "password" or "rememberToken" in the columns definitions, its
        // an User model and we should point that. We will later revisit the Model type
        // when creating relations to change it to Pivot or Morph Pivot if necessary.
        if (Arr::has($data, 'columns.password') || Arr::has($data, 'columns.rememberToken')) {
            $data['type'] = 'user';
        }
    }

    /**
     * Set the hidden columns
     *
     * @param  array  $data
     */
    protected function setHiddenColumns(array &$data)
    {
        $hidden = [];

        foreach (Arr::get($data, 'columns') as $column => $line) {
            if (Str::contains($column, Column::HIDDEN)) {
                $hidden[] = Column::isShorthand($column)
                    ? Column::getShorthandDefault($column, $line)
                    : $column;
            }
        }

        Arr::set($data, 'hidden', $hidden);
    }
}
