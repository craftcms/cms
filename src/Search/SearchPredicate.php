<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * A generated search condition and its parameter bindings.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class SearchPredicate implements Expression
{
    /** @param array{sql: string, bindings: list<int|float|string|bool|null>} $condition */
    public function __construct(private array $condition) {}

    public function getValue(Grammar $grammar): string
    {
        return $this->condition['sql'];
    }

    /** @return list<int|float|string|bool|null> */
    public function bindings(): array
    {
        return $this->condition['bindings'];
    }
}
