<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\Timestamps;

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

        if (! $construction->model->is_cast_enabled) {
            foreach ($columns as $column) {
                $construction->class->addComment($this->generateGenericComment($column));
            }
        } else {
            foreach ($columns as $column) {
                $construction->class->addComment($this->generateComment($construction, $column));
            }
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

    /**
     * Generates a string for columns "uncasted".
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     *
     * @return string
     */
    protected function generateGenericComment(Column $column): string
    {
        return static::generateCommentStart($column) . 'mixed $' . $column->name;
    }

    /**
     * Generates a string for a column based on their real type.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     *
     * @return string
     */
    protected function generateComment(ModelConstruction $construction, Column $column): string
    {
        $start = static::generateCommentStart($column);

        // If the class has a cast, we will get the cast type.
        /** @var \Larawiz\Larawiz\Lexing\Database\QuickCast|null $cast */
        $cast = $construction->model->quickCasts->get($column->name);

        if ($cast && $cast->overridesType()) {
            if (ctype_lower($cast->getCommentType()[0])) {
                $start .= $cast->getCommentType();
            } else {
                $start .= Str::start($cast->getCommentType(), '\\');
            }
        } else {
            $start .= $column->phpType();
        }

        return  $start . ' $' . $column->name;
    }

    /**
     * Generate the start of the comment.
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     *
     * @return string
     */
    protected static function generateCommentStart(Column $column): string
    {
        return '@property ' . ($column->isNullable() ? 'null|' : '');
    }
}
