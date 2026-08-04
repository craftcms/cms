<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Queries;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

readonly class ExcludeDescendantIdsExpression implements Expression
{
    /**
     * @param  int[]  $elementIds  The element IDs to exclude
     */
    public function __construct(
        private array $elementIds,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        if ($this->elementIds === []) {
            return '1 = 1';
        }

        return sprintf(
            '%s not in (%s)',
            $grammar->wrap('elements.id'),
            implode(', ', array_map(static fn (mixed $elementId): string => (string) $elementId, $this->elementIds)),
        );
    }
}
