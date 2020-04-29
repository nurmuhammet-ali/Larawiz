<?php

namespace Larawiz\Larawiz\Scaffolding\Pipes;

use Illuminate\Support\Arr;
use Larawiz\Larawiz\Scaffold;
use Illuminate\Config\Repository;

class ParseDatabaseData extends BaseParserPipe
{
    /**
     * @inheritDoc
     */
    protected $exceptionIfNoFile = true;

    /**
     * @inheritDoc
     */
    protected function setRepository(Scaffold $scaffold, array $data)
    {
        $scaffold->rawDatabase->set('models', Arr::get($data, 'models', []));
        $scaffold->rawDatabase->set('migrations', Arr::get($data, 'migrations', []));
        $scaffold->rawDatabase->set('namespace', Arr::get($data, 'namespace', null));
    }
}
