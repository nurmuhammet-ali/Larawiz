<?php

namespace Larawiz\Larawiz\Parsers\Database\Pipes;

use Closure;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Model;
use Larawiz\Larawiz\Lexing\Database\SoftDelete;
use Larawiz\Larawiz\Scaffold;
use LogicException;

class ParseModelSoftDeletesColumns
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
        foreach ($scaffold->database->models as $key => $model) {
            if ($softDeletes = $this->getSoftDeletesColumn($model)) {
                $this->setSoftDeletes($model, $softDeletes);
            }
        }

        return $next($scaffold);
    }

    /**
     * Get all the soft-deletes columns
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @return \Larawiz\Larawiz\Lexing\Database\Column
     */
    protected function getSoftDeletesColumn(Model $model)
    {
        $softDeletes = $model->columns->filter(function ($column) {
            return $column && $column->isSoftDeletes();
        });

        if ($softDeletes->count() > 1) {
            throw new LogicException("The [{$model->key}] has more than one [softDeletes] column.");
        }

        return $softDeletes->first();
    }

    /**
     * Sets the soft-deleting column into the model.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Model  $model
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $softDeletes
     */
    protected function setSoftDeletes(Model $model, Column $softDeletes)
    {
        // We will set the default name for the soft-delete column if there is not issued.
        if (in_array($softDeletes->name, ['softDeletes', 'softDeletesTz'])) {
            $softDeletes->name = SoftDelete::COLUMN;
        }

        $model->softDelete->using = true;
        $model->softDelete->column = $softDeletes->name;
    }
}
