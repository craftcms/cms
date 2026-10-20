<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * Holds the place of a query's default order. It's ignored once any other order is set, so explicit orders replace
 * the default one.
 *
 * @since 6.0.0
 */
class OrderByPlaceholderExpression implements Expression
{
    /**
     * @param  Expression|null  $default  An order to use in place of the query's normal default order, such as a
     *                                    relation field's fixed order
     */
    public function __construct(
        public readonly ?Expression $default = null,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        return '';
    }
}
