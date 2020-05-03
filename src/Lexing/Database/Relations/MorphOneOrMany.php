<?php

namespace Larawiz\Larawiz\Lexing\Database\Relations;

/**
 * @property bool $withDefault  If it should use a default empty model instance when null.
 *
 * @property null|\Larawiz\Larawiz\Lexing\Database\Model $pivot  The Pivot model, if assessed
 * @property \Illuminate\Support\Collection|string[] $withPivot  Columns to retrieve from the Pivot
 *
 * @property string $relationKey  The relation name containing the morphs in the target model.
 *
 */
class MorphOneOrMany extends BaseRelation
{

}
