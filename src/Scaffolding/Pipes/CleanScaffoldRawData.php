<?php

namespace Larawiz\Larawiz\Scaffolding\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;

class CleanScaffoldRawData
{
    /**
     * Handle the constructing scaffold data.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        // Here we will remove the raw data for the parsing for better memory management.
        foreach ($scaffold->getAttributes() as $name => $attribute) {
            if (Str::startsWith($name, 'raw')) {
                unset($scaffold[$name]);
            }
        }

        return $next($scaffold);
    }
}
