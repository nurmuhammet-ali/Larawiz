<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Larawiz\Larawiz\Console\ApplicationBackup;
use Larawiz\Larawiz\Larawiz;
use Symfony\Component\Yaml\Parser;
use Tests\Mockery\ArrayFilesystem;

use const DIRECTORY_SEPARATOR as DS;

trait MocksDatabaseFile
{
    /**
     * @var \Tests\Mockery\ArrayFilesystem
     */
    protected $filesystem;

    /**
     * @var \Mockery\MockInterface|\Symfony\Component\Yaml\Parser
     */
    protected $parser;

    protected function mockDatabaseFile(array $array)
    {
        $this->shouldUseArrayFilesystem();

        $this->shouldMockDatabaseScaffold();
        $this->shouldMockSeederFile();
        $this->shouldMockScopeFile();
        $this->shouldMockTraitFile();
        $this->shouldMockUuidTraitFile();

        $this->mock(ApplicationBackup::class)->shouldReceive('backup')->andReturnTrue();

        $this->parser = $this->mock(Parser::class)
            ->shouldReceive('parse')
            ->andReturn($array);
    }

    protected function shouldUseArrayFilesystem($should = true)
    {
        $this->filesystem = $this->instance(Filesystem::class, $should ? new ArrayFilesystem : new Filesystem);
    }

    protected function shouldMockDatabaseScaffold($should = true)
    {
        $path = $this->app->basePath(Larawiz::PATH . DS . 'database.yaml');

        $this->filesystem->put($path, $should ? '' : file_get_contents($path));
    }

    protected function shouldMockSeederFile($should = true)
    {
        $path = Larawiz::getDummyPath('DummySeeder.stub');

        $this->filesystem->put($path, $should ? '' : file_get_contents($path));
    }

    protected function shouldMockScopeFile($should = true)
    {
        $path = Larawiz::getDummyPath('DummyScope.stub');

        $this->filesystem->put($path, $should ? '' : file_get_contents($path));
    }

    protected function shouldMockCastFile($should = true)
    {
        $path = Larawiz::getDummyPath('DummyCast.stub');

        $this->filesystem->put($path, $should ? '' : file_get_contents($path));
    }

    protected function shouldMockTraitFile($should = true)
    {
        $path = Larawiz::getDummyPath('DummyTrait.stub');

        $this->filesystem->put($path, $should ? '' : file_get_contents($path));
    }

    protected function shouldMockUuidTraitFile($should = true)
    {
        $path = Larawiz::getDummyPath('HasUuidPrimaryKey.stub');

        $this->filesystem->put($path, $should ? '' : file_get_contents($path));
    }

    protected function assertFileExistsInFilesystem(string $path)
    {
        if ($this->filesystem instanceof ArrayFilesystem) {
            return $this->assertTrue($this->filesystem->exists($path), "The [{$path}] file doesnt exists.");
        }

        return $this->assertFileExists($path);
    }

    protected function assertFileNotExistsInFilesystem(string $path)
    {
        if ($this->filesystem instanceof ArrayFilesystem) {
            return $this->assertFalse($this->filesystem->exists($path), "The [{$path}] file exists.");
        }

        return $this->assertFileExists($path);
    }
}
