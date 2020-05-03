<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Timestamps;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetColumnComments
{
    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        $columns = $this->commentableColumns($construction->model->columns, $construction->model->timestamps);

        foreach ($columns as $column) {
            $start = '@property ';

            if ($column->isNullable()) {
                $start .= 'null|';
            }

            $construction->class->addComment($start . $column->phpType() . ' $' . $column->name);
        }

        $construction->class->addComment('');

        return $next($construction);
    }

    /**
     * Return all commentable columns for the model.
     *
     * @param  \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[]  $columns
     * @param  \Larawiz\Larawiz\Lexing\Database\Timestamps  $timestamps
     * @return \Illuminate\Support\Collection|\Larawiz\Larawiz\Lexing\Database\Column[]
     */
    protected function commentableColumns(Collection $columns, Timestamps $timestamps)
    {
        return $columns->filter(function (Column $column) use ($timestamps) {
            return $column->hidesRealBlueprintMethods() && $timestamps->notTimestamps($column->name);
        });
    }
}
