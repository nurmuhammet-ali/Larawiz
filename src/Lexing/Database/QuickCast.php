<?php

namespace Larawiz\Larawiz\Lexing\Database;

use Illuminate\Support\Fluent;
use Larawiz\Larawiz\Helpers;
use Larawiz\Larawiz\Lexing\HasNamespaceAndPath;

/**
 * @property string $column
 *
 * @property string|null $type
 * @property string $cast
 *
 * @property bool $is_class
 * @property bool $external
 */
class QuickCast extends Fluent
{
    use HasNamespaceAndPath;

    /**
     * All of the attributes set on the fluent instance.
     *
     * @var array
     */
    protected $attributes = [
        'external' => false,
        'is_class' => false,
    ];

    /**
     * Checks if the type is overriden.
     *
     * @return bool
     */
    public function overridesType(): bool
    {
        return (bool) $this->type;
    }

    /**
     * Returns the comment to add as PHP Doc type.
     *
     * @return string
     */
    public function getCommentType(): string
    {
        return Helpers::castTypeToPhpType($this->type);
    }
}
