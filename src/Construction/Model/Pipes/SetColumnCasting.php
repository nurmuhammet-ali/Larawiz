<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Lexing\Database\Column;
use Larawiz\Larawiz\Lexing\Database\QuickCast;
use Nette\PhpGenerator\Literal;

class SetColumnCasting
{
    /**
     * Handle the model construction
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        $castedValues = [];

        foreach ($construction->model->columns as $column) {
            // If there is already a cast for the column, we will use that.
            if ($cast = $construction->model->quickCasts->get($column->name)) {
                $castedValues[$column->name] = $cast;
            } elseif ($this->columnShouldBeCasted($column)) {
                $castedValues[$column->name] = $column->castType();
            }
        }

        if (! empty($castedValues)) {
            // For each casted value, if its a quick cast, we will add it to the
            // namespace, and change the value as a Literal so it can be used.
            foreach ($castedValues as $column => $cast) {
                if ($cast instanceof QuickCast) {
                    $construction->namespace->addUse($cast->fullRootNamespace());
                    $castedValues[$column] = new Literal($cast->class . '::class');
                }
            }

            // Then, we will just add them.
            $construction->class->addProperty('casts', $castedValues)
                ->setProtected()
                ->addComment('The attributes that should be cast.')
                ->addComment('')
                ->addComment('@var array');
        }

        return $next($construction);
    }

    /**
     * Determines if the column should be casted in the array,
     *
     * @param  \Larawiz\Larawiz\Lexing\Database\Column  $column
     * @return bool
     */
    protected function columnShouldBeCasted(Column $column): bool
    {
        return $column->castType() !== 'string'
            && ! $column->relation
            && ! $column->isPrimary()
            && ! $column->isTimestamps()
            && ! $column->isSoftDeletes()
            && ! $column->shouldCastToDate();
    }
}
