<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Expressions\JsonExtract;
use Illuminate\Database\Query\Grammars\MariaDbGrammar;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Support\Facades\DB;

it('extracts JSON values as text in each database’s syntax', function (string $grammar, string $sql) {
    expect(new JsonExtract('analysis', ['colors', 'dominant'])->getValue(new $grammar(DB::connection())))->toBe($sql);
})->with([
    'MySQL' => [MySqlGrammar::class, '(`analysis`->>\'$."colors"."dominant"\')'],
    'MariaDB' => [MariaDbGrammar::class, 'JSON_UNQUOTE(JSON_EXTRACT(`analysis`, \'$."colors"."dominant"\'))'],
    'PostgreSQL' => [PostgresGrammar::class, '("analysis"#>>\'{"colors","dominant"}\')'],
    'SQLite' => [SQLiteGrammar::class, '("analysis"->>\'$."colors"."dominant"\')'],
]);
