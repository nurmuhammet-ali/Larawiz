<?php

namespace Larawiz\Larawiz\Lexing\Database\Relations;

class HasOneOrMany extends BaseRelation
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
}
