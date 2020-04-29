<?php

namespace Larawiz\Larawiz\Scaffolding\Pipes;

use Larawiz\Larawiz\Parsers\Database\DatabaseParserPipeline;

class LexDatabaseData extends BaseLexPipe
{
    /**
     * Pipeline to Lex the raw YAML contents.
     *
     * @var string
     */
    protected $pipeline = DatabaseParserPipeline::class;
}
