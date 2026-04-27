<?php

declare(strict_types=1);

namespace CraftCms\Cms\Database\Expressions;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Grammar;
use Tpetry\QueryExpressions\Concerns\IdentifiesDriver;
use Tpetry\QueryExpressions\Concerns\StringizeExpression;

/**
 * Extension of {@see \Tpetry\QueryExpressions\Language\Cast}
 * without exception on custom cast.
 */
readonly class Cast implements Expression
{
    use IdentifiesDriver;
    use StringizeExpression;

    public function __construct(
        private string|Expression $expression,
        private string $type,
    ) {}

    public function getValue(Grammar $grammar): string
    {
        $expression = $this->stringize($grammar, $this->expression);

        return match ($this->identify($grammar)) {
            'mariadb', 'mysql' => $this->castMysql($expression),
            'pgsql' => $this->castPgsql($expression),
            'sqlite' => $this->castSqlite($expression),
            'sqlsrv' => $this->castSqlsrv($expression),
        };
    }

    private function castMysql(float|int|string $expression): string
    {
        // MySQL 5.7 does not support casting to floating-point numbers. So the workaround is to multiply with one to
        // trigger MySQL's automatic type conversion. Technically, this will always produce a double value and never a
        // float one, but it will be silently downsized to a float when stored in a table.
        return match ($this->type) {
            'bigint', 'int' => "cast({$expression} as signed)",
            'float', 'double' => "(({$expression})*1.0)",
            default => "cast({$expression} as {$this->type})",
        };
    }

    private function castPgsql(float|int|string $expression): string
    {
        return match ($this->type) {
            'bigint' => "cast({$expression} as bigint)",
            'float' => "cast({$expression} as real)",
            'double' => "cast({$expression} as double precision)",
            'int' => "cast({$expression} as int)",
            default => "cast({$expression} as {$this->type})",
        };
    }

    private function castSqlite(float|int|string $expression): string
    {
        return match ($this->type) {
            'bigint', 'int' => "cast({$expression} as integer)",
            'float', 'double' => "cast({$expression} as real)",
            default => "cast({$expression} as {$this->type})",
        };
    }

    private function castSqlsrv(float|int|string $expression): string
    {
        return match ($this->type) {
            'bigint' => "cast({$expression} as bigint)",
            'float' => "cast({$expression} as float(24))",
            'double' => "cast({$expression} as float(53))",
            'int' => "(({$expression})*1)",
            default => "cast({$expression} as {$this->type})",
        };
    }
}
