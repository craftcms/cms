<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexSourcesController;
use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ExportElementIndexController;
use CraftCms\Cms\Http\Controllers\Gql\ApiController as GqlApiController;
use CraftCms\Cms\Http\Middleware\UseWriteConnection;
use Illuminate\Database\Connection;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;

it('uses the write connection for unsafe requests', function (string $method) {
    $db = Mockery::mock(Connection::class);
    $db->expects('useWriteConnectionWhenReading')->with(true)->once()->andReturnSelf();
    $request = Request::create('/articles', $method);

    $response = new UseWriteConnection($db)->handle($request, fn () => 'response');

    expect($response)->toBe('response');
})->with(['POST', 'PATCH', 'DELETE']);

it('keeps safe requests on the read connection', function (string $method) {
    $db = Mockery::mock(Connection::class);
    $db->expects('useWriteConnectionWhenReading')->with(false)->once()->andReturnSelf();
    $request = Request::create('/articles', $method);

    $response = new UseWriteConnection($db)->handle($request, fn () => 'response');

    expect($response)->toBe('response');
})->with(['GET', 'HEAD']);

it('keeps read-only controller actions on the read connection', function (string $controller, string $method) {
    $db = Mockery::mock(Connection::class);
    $db->expects('useWriteConnectionWhenReading')->with(false)->once()->andReturnSelf();
    $request = Request::create('/renamed-endpoint', 'POST');
    $route = new Route(['POST'], '/renamed-endpoint', $method === '__invoke' ? $controller : [$controller, $method]);
    $request->setRouteResolver(fn () => $route);

    $response = new UseWriteConnection($db)->handle($request, fn () => 'response');

    expect($response)->toBe('response');
})->with([
    'count elements' => [ElementIndexController::class, 'countElements'],
    'export' => [ExportElementIndexController::class, '__invoke'],
    'get elements' => [ElementIndexController::class, 'getElements'],
    'get more elements' => [ElementIndexController::class, 'getMoreElements'],
    'get source tree HTML' => [ElementIndexSourcesController::class, 'getSourceTreeHtml'],
    'GraphQL API' => [GqlApiController::class, '__invoke'],
]);

it('resets the write connection after an unsafe request', function () {
    $db = DB::connection();
    $writePdo = new PDO('sqlite::memory:');
    $readPdo = new PDO('sqlite::memory:');
    $writePdo->exec("CREATE TABLE markers (value TEXT); INSERT INTO markers VALUES ('write')");
    $readPdo->exec("CREATE TABLE markers (value TEXT); INSERT INTO markers VALUES ('read')");
    $db->setPdo($writePdo);
    $db->setReadPdo($readPdo);
    $middleware = new UseWriteConnection($db);

    $writeValue = $middleware->handle(
        Request::create('/articles', 'POST'),
        fn () => $db->table('markers')->value('value'),
    );
    $readValue = $middleware->handle(
        Request::create('/articles', 'GET'),
        fn () => $db->table('markers')->value('value'),
    );

    expect($writeValue)->toBe('write')
        ->and($readValue)->toBe('read');
});
