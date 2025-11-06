<?php

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

final class JsonExtract implements Expression
{
    use IdentifiesDriver;
    use StringizeExpression;

    public function __construct(
        private readonly string|Expression $expression,
        private readonly string $path,
    ) {
    }

    public function getValue(Grammar $grammar): string
    {
        $expression = $this->stringize($grammar, $this->expression);

        return match ($this->identify($grammar)) {
            'mariadb' => "JSON_UNQUOTE(JSON_EXTRACT($expression, $this->path))",
            default => "($expression->>$this->path)",
        };
    }
}
