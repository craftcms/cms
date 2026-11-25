<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Expr;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('compiles expressions in each database’s syntax', function (Expression $expression, string $grammar, string $sql) {
    expect($expression->getValue(new $grammar(DB::connection())))->toBe($sql);
})->with([
    'nested aggregate (MySQL)' => [Expr::alias(Expr::coalesce([Expr::sum('uses'), Expr::number(0)]), 'uses'), MySqlGrammar::class, 'coalesce(sum(`uses`), 0) as `uses`'],
    'nested aggregate (PostgreSQL)' => [Expr::alias(Expr::coalesce([Expr::sum('uses'), Expr::number(0)]), 'uses'), PostgresGrammar::class, 'coalesce(sum("uses"), 0) as "uses"'],
    'is not null (SQLite)' => [Expr::isNotNull('catalogPricingRuleId'), SQLiteGrammar::class, '("catalogPricingRuleId" is not null)'],
    'xor (MySQL)' => [Expr::xor(Expr::isNull('a'), Expr::equal('b', Expr::number(1))), MySqlGrammar::class, '((`a` is null) xor (`b` = 1))'],
    'xor (PostgreSQL)' => [Expr::xor(Expr::isNull('a'), Expr::equal('b', Expr::number(1))), PostgresGrammar::class, '((("a" is null) and not ("b" = 1)) or (not ("a" is null) and ("b" = 1)))'],
    'portable cast (MariaDB)' => [Expr::cast('price', 'int'), MariaDbGrammar::class, 'cast(`price` as signed)'],
    'custom cast (PostgreSQL)' => [Expr::cast('price', 'decimal(14,4)'), PostgresGrammar::class, 'cast("price" as decimal(14,4))'],
    'custom cast (SQLite)' => [Expr::cast('price', 'decimal(14,4)'), SQLiteGrammar::class, 'cast("price" as decimal(14,4))'],
]);

it('builds queries that run on the current database', function () {
    Schema::create('expr_test', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->integer('a')->nullable();
        $table->integer('b');
    });

    try {
        DB::table('expr_test')->insert([
            ['name' => 'x', 'a' => null, 'b' => 5],
            ['name' => 'y', 'a' => 2, 'b' => 10],
            ['name' => 'z', 'a' => 3, 'b' => 4],
        ]);

        $rows = DB::table('expr_test')
            ->select([
                'name',
                Expr::alias(Expr::subtract('b', 'a'), 'diff'),
                Expr::alias(Expr::case([Expr::caseRule(Expr::value('big'), Expr::greaterThan('b', Expr::number(4)))], Expr::value('small')), 'size'),
            ])
            ->where(Expr::isNotNull('a'))
            ->orderBy(Expr::subtract('b', 'a'), 'desc')
            ->get()
            ->map(fn (object $row) => [$row->name, (int) $row->diff, $row->size])
            ->all();

        expect($rows)->toBe([['y', 8, 'big'], ['z', 1, 'small']]);

        $total = fn (Expression $filter) => (int) DB::table('expr_test')
            ->select(Expr::alias(Expr::coalesce([Expr::sum('a'), Expr::number(0)]), 'total'))
            ->where($filter)
            ->value('total');

        expect($total(Expr::equal('name', Expr::value('x'))))->toBe(0)
            ->and($total(Expr::or(Expr::equal('name', Expr::value('y')), Expr::lessThan('b', Expr::number(5)))))->toBe(5)
            ->and($total(Expr::not(Expr::between('b', Expr::number(5), Expr::number(10)))))->toBe(3);
    } finally {
        Schema::dropIfExists('expr_test');
    }
});
