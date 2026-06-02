<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\ConditionExpression;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

readonly class JsonContains implements ConditionExpression
{
    use IdentifiesDriver;
    use StringizeExpression;

    public function __construct(
        private string|Expression $expression,
        private mixed $value,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $expression = $this->stringize($grammar, $this->expression);
        $value = $this->prepareValue($grammar);

        return match ($this->identify($grammar)) {
            'mysql', 'mariadb' => "json_contains($expression, $value)",
            'pgsql' => "($expression)::jsonb @> $value",
            'sqlsrv' => "$value in (select [value] from openjson($expression))",
            'sqlite' => sprintf(
                'exists (select 1 from json_each(%s) where %s is %s)',
                $expression,
                $grammar->wrap('json_each.value'),
                $value,
            ),
        };
    }

    private function prepareValue(Grammar $grammar): string
    {
        $value = match ($this->identify($grammar)) {
            'sqlite' => $this->value,
            'sqlsrv' => is_bool($this->value) ? json_encode($this->value) : $this->value,
            default => json_encode($this->value, JSON_UNESCAPED_UNICODE),
        };

        return $grammar->escape($value);
    }
}
