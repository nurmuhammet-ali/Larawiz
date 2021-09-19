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

        if (!empty($scopes)) {
            $construction->class->addComment('');

            $construction->namespace->addUse(Builder::class);

            foreach ($construction->model->localScopes->unique() as $scope) {
                $name = lcfirst(ltrim($scope, 'scope'));

                $construction->class
                    ->addMethod($scope)
                    ->setProtected()
                    ->addComment('Query scope for "' . Str::of($name)->snake(' ') . '".')
                    ->addComment('')
                    ->addComment('@param  \Illuminate\Database\Eloquent\Builder|static  $query')
                    ->addComment('@return void')
                    ->addBody("// TODO: Filter the query by the '$name' scope.")
                    ->addParameter('query')
                    ->setType(Builder::class);

                $construction->class
                    ->addComment(
                        "@method \Illuminate\Database\Eloquent\Builder|static $name()"
                    );
            }
        }

        return $next($construction);
    }
}
