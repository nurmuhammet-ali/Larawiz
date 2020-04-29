<?php

namespace Larawiz\Larawiz\Construction\Model\Pipes;

use Closure;
use Nette\PhpGenerator\PhpFile;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Factory;
use Larawiz\Larawiz\Construction\Model\ModelConstruction;

class WriteSeeder
{
    /**
     * Console.
     *
     * @var \Illuminate\Contracts\Console\Kernel
     */
    protected $console;

    /**
     * WriteModel constructor.
     *
     * @param  \Illuminate\Contracts\Console\Kernel  $console
     */
    public function __construct(Kernel $console)
    {
        $this->console = $console;
    }

    /**
     * Handle the model construction.
     *
     * @param  \Larawiz\Larawiz\Construction\Model\ModelConstruction  $construction
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(ModelConstruction $construction, Closure $next)
    {
        if ($construction->model->seeder) {
            $this->console->call('make:seeder', [
                'name' => $construction->model->key . 'Seeder',
            ]);
        }

        return $next($construction);
    }
}
