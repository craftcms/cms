<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;
use Tpetry\QueryExpressions\Language\CaseGroup;
use Tpetry\QueryExpressions\Language\CaseRule;
use Tpetry\QueryExpressions\Operator\Comparison\Equal;
use Tpetry\QueryExpressions\Value\Value;

final readonly class FixedOrderExpression implements Expression
{
    use StringizeExpression;

    public function __construct(
        private string|Expression $column,
        private array $values,
    ) {}

    #[\Override]
    public function getValue(Grammar $grammar): string
    {
        $cases = [];
        $key = -1;

        foreach ($this->values as $key => $value) {
            $cases[] = new CaseRule(new Value($key), new Equal($this->column, new Value($value)));
        }

        return new CaseGroup($cases, new Value($key))->getValue($grammar);
    }
}
