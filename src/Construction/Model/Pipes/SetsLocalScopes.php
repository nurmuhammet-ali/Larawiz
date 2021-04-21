<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetsLocalScopes
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
        $scopes = $construction->model->localScopes;

        if (null !== $scopes && !empty($scopes)) {
            $construction->namespace->addUse(Builder::class);

            foreach (array_unique($construction->model->localScopes) as $scope) {
                $construction->class
                    ->addMethod($name = Str::of($scope)->camel()->ucfirst()->start('scope'))
                    ->setProtected()
                    ->addComment("Query scope for $scope.")
                    ->addComment('')
                    ->addComment('@param  \Illuminate\Database\Eloquent\Builder  $query')
                    ->addComment('@return void')
                    ->addBody('// $query')
                    ->addParameter('query')
                    ->setType(Builder::class);

                $construction->class
                    ->addComment(
                        "@method \Illuminate\Database\Eloquent\Builder " .
                        $name->ltrim('scope')->camel() . '()'
                    );
            }
        }

        return $next($construction);
    }
}
