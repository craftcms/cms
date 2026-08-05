<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

readonly class JsonExtract implements Expression
{
    use IdentifiesDriver;
    use StringizeExpression;

    /** @param string|list<string> $path */
    public function __construct(
        private string|Expression $expression,
        private string|array $path,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $expression = $this->stringize($grammar, $this->expression);
        $path = $this->formatPath($grammar);

        return match ($this->identify($grammar)) {
            'mariadb' => "JSON_UNQUOTE(JSON_EXTRACT($expression, $path",
            'pgsql' => "($expression#>>$path)",
            default => "($expression->>$path)",
        };
    }

    private function formatPath(Grammar $grammar): string
    {
        $path = Arr::wrap($this->path);

        return $grammar->quoteString(match ($this->identify($grammar)) {
            'pgsql' => sprintf('{%s}', implode(',', array_map(fn (string $seg) => sprintf('"%s"', $seg), $path))),
            default => sprintf('$.%s', implode('.', array_map(fn (string $seg) => sprintf('"%s"', $seg), $path))),
        });
    }
}
