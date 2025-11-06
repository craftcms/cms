<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

final readonly class JsonExtract implements Expression
{
    use IdentifiesDriver;
    use StringizeExpression;

    public function __construct(
        private string|Expression $expression,
        private string $path,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $expression = $this->stringize($grammar, $this->expression);

        return match ($this->identify($grammar)) {
            'mariadb' => "JSON_UNQUOTE(JSON_EXTRACT($expression, $this->path))",
            default => "($expression->>$this->path)",
        };
    }
}
