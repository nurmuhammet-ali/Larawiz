<?php

namespace Tests\Commands;

use Tests\RegistersPackage;
use Larawiz\Larawiz\Larawiz;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\File;
use Tests\CleansProjectFromScaffoldData;
use const DIRECTORY_SEPARATOR as DS;

class ClearBackupsTest extends TestCase
{
    use RegistersPackage;
    use CleansProjectFromScaffoldData;

    public function test_deletes_backups()
    {
        $directory = storage_path(Larawiz::BACKUPS_DIR) . DS . 'foo';

        File::makeDirectory($directory, null, true, true);

        $this->artisan('larawiz:clear-backups')
            ->expectsConfirmation('Are you sure to delete ALL your project backups?', 'yes');

        $this->assertDirectoryNotExists($directory);
    }

    public function test_does_nothing_when_no_backups_exists()
    {
        $this->artisan('larawiz:clear-backups')
            ->expectsOutput('There are no backups file, so nothing was deleted.');
    }

    public function test_deletes_backups_forcefully()
    {
        $directory = storage_path(Larawiz::BACKUPS_DIR) . DS . 'foo';

        File::makeDirectory($directory, null, true, true);

        $this->artisan('larawiz:clear-backups --force');

        $this->assertDirectoryNotExists($directory);
    }

    public function test_doesnt_deletes_backups_when_doesnt_confirm()
    {
        $directory = storage_path(Larawiz::BACKUPS_DIR) . DS . 'foo';

        File::makeDirectory($directory, null, true, true);

        $this->artisan('larawiz:clear-backups')
            ->expectsConfirmation('Are you sure to delete ALL your project backups?');

        $this->assertDirectoryExists($directory);
    }

    protected function tearDown() : void
    {
        $this->cleanProject();

        parent::tearDown();
    }
}
