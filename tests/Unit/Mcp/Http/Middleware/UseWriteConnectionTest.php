<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\Http\Middleware\UseWriteConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

it('reads from the write connection only on unsafe requests', function () {
    $db = DB::connection();
    $writePdo = new PDO('sqlite::memory:');
    $readPdo = new PDO('sqlite::memory:');
    $writePdo->exec("CREATE TABLE markers (value TEXT); INSERT INTO markers VALUES ('write')");
    $readPdo->exec("CREATE TABLE markers (value TEXT); INSERT INTO markers VALUES ('read')");
    $db->setPdo($writePdo);
    $db->setReadPdo($readPdo);
    $middleware = new UseWriteConnection($db);

    $postValue = $middleware->handle(
        Request::create('/mcp', 'POST'),
        fn () => $db->table('markers')->value('value'),
    );
    $getValue = $middleware->handle(
        Request::create('/mcp', 'GET'),
        fn () => $db->table('markers')->value('value'),
    );

    expect($postValue)->toBe('write')
        ->and($getValue)->toBe('read');
});
