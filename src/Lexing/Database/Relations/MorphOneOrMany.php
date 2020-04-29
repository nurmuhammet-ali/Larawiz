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
    /**
     * Returns if the Has Relation returns a collection of items.
     *
     * @return bool
     */
    public function isMany()
    {
        return in_array($this->type, parent::RETURN_COLLECTIONS, true);
    }

    /**
     * Returns the Column name of the morph.
     *
     * @return string
     */
    public function getColumnName()
    {
        return $this->column ?? $this->name;
    }
}
