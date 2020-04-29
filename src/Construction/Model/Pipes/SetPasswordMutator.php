<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Illuminate\Foundation\Auth\User;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class SetPasswordMutator
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
        if ($construction->model->modelType === User::class && $construction->model->columns->has('password')) {
            $construction->class
                ->addMethod('setPasswordAttribute')
                ->setPublic()
                ->addComment('Automatically encrypts the password.')
                ->addComment('')
                ->addComment('@param  string  $password')
                ->addComment('@return void')
                ->addBody("\$this->attributes['password'] = app('hash')->make(\$password);")
                ->addParameter('password');
        }

        return $next($construction);
    }
}
