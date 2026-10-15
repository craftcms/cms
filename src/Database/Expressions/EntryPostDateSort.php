<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;

/**
 * @internal
 *
 * @since 6.0.0
 */
readonly class EntryPostDateSort implements Expression
{
    public function __construct(private int $direction) {}

    public function getValue(Grammar $grammar): string
    {
        $column = $grammar->wrap('entries.postDate');

        if ($this->direction === SORT_ASC) {
            return $grammar instanceof MySqlGrammar
                ? "$column IS NOT NULL DESC, $column ASC"
                : "$column ASC NULLS LAST";
        }

        return $grammar instanceof MySqlGrammar
            ? "$column IS NULL DESC, $column DESC"
            : "$column DESC NULLS FIRST";
    }
}
