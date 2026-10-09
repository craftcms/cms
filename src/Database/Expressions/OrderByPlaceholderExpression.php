<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;

/**
 * @since 6.0.0
 */
class OrderByPlaceholderExpression implements Expression
{
    public function getValue(Grammar $grammar): string
    {
        return '';
    }
}
