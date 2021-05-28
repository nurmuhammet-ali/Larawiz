<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;
use Larawiz\Larawiz\Helpers;
use LogicException;

class SetAppend
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
        if ($construction->model->append->isNotEmpty()) {
            // Only get the attributes which the model doesn't have
            $keys = $construction->model->append->keys()->diff(
                $construction->model->columns->map->name
            )->values();

            $construction->class->addProperty('append', $keys->toArray())
                ->setProtected()
                ->addComment("The accessors to append to the model's array form.")
                ->addComment('')
                ->addComment('@var array');

            foreach ($construction->model->append as $name => $type) {
                try {
                    $realType = Helpers::castTypeToPhpType($type);
                } catch (LogicException $exception) {
                    throw new LogicException(
                        "The $type class doesn't exists for the appended [$name] of [$construction->model->class]"
                    );
                }

                // If the model already has set the column, don't append it.
                if (! $keys->has($name)) {
                    $construction->class->addComment("@property-read $realType $$name");
                }

                $construction->class->addMethod('get' . ucfirst(Str::camel($name)) . 'Attribute')
                    ->setProtected()
                    ->addComment("Returns the '$name' attribute.")
                    ->addComment('')
                    ->addComment("@return $realType")
                    ->addBody("// TODO: Code the '$name' getter.");
            }

            $construction->class->addComment('');
        }

        return $next($construction);
    }
}
