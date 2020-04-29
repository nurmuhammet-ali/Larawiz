<?php

namespace Larawiz\Larawiz\Scaffolding\Pipes;

use Closure;
use Illuminate\Support\Str;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Contracts\Container\Container;
use Larawiz\Larawiz\Parsing\Http\HttpParserPipeline;

abstract class BaseLexPipe
{
    /**
     * Pipeline to Lex the data.
     *
     * @var string
     */
    protected $pipeline;

    /**
     * Application Service Container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * LexScaffoldData constructor.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Handle the constructing scaffold data.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @param  \Closure  $next
     *
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(Scaffold $scaffold, Closure $next)
    {
        $this->lexDataWithPipeline($scaffold);

        return $next($scaffold);
    }

    /**
     * Runs a pipeline to lex the given data from the Scaffold.
     *
     * @param  \Larawiz\Larawiz\Scaffold  $scaffold
     * @return \Larawiz\Larawiz\Scaffold
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function lexDataWithPipeline(Scaffold $scaffold)
    {
        return $this->container->make($this->pipeline)->send($scaffold)->thenReturn();
    }
}
