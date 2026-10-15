<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * @internal
 *
 * @since 6.0.0
 */
readonly class SourceSortExpression implements Expression
{
    public function __construct(
        private string|Expression $orderBy,
        private int $direction,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $sql = $this->orderBy instanceof Expression ? $this->orderBy->getValue($grammar) : $this->orderBy;
        $direction = $this->direction === SORT_ASC ? 'ASC' : 'DESC';

        return "$sql $direction";
    }
}
