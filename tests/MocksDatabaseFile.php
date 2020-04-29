<?php

namespace Tests;

use Larawiz\Larawiz\Larawiz;
use Symfony\Component\Yaml\Parser;
use Illuminate\Filesystem\Filesystem;
use const DIRECTORY_SEPARATOR as DS;

trait MocksDatabaseFile
{
    /**
     * @var \Mockery\MockInterface|\Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * @var \Mockery\MockInterface|\Symfony\Component\Yaml\Parser
     */
    protected $parser;

    protected function mockDatabaseFile(array $array = null)
    {
        $this->filesystem = $this->partialMock(Filesystem::class);

        $this->filesystem->shouldReceive('exists')
            ->with($this->app->basePath(Larawiz::PATH . DS . 'database.yaml'))
            ->andReturnTrue();
        $this->filesystem->shouldReceive('get')
            ->with($this->app->basePath(Larawiz::PATH . DS . 'database.yaml'))
            ->andReturn('');

        $this->parser = $this->mock(Parser::class)
            ->shouldReceive('parse')
            ->andReturn($array ?? [
                'models' => [
                    'Foo' => [
                        'bar' => 'string'
                    ]
                ]
            ]);
    }
}
