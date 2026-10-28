<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\Elements\ElementIndex\ElementIndexController;
use CraftCms\Cms\Http\Middleware\PreventStaleDatabaseReads;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->db = DB::connection();
    $writePdo = new PDO('sqlite::memory:');
    $readPdo = new PDO('sqlite::memory:');
    $writePdo->exec("CREATE TABLE markers (value TEXT); INSERT INTO markers VALUES ('write')");
    $readPdo->exec("CREATE TABLE markers (value TEXT); INSERT INTO markers VALUES ('read')");
    $this->db->setPdo($writePdo);
    $this->db->setReadPdo($readPdo);
    $this->db->forgetRecordModificationState();

    $this->session = app(SessionManager::class)->driver();
    $this->send = function (Request $request, Closure $next): mixed {
        $request->setLaravelSession($this->session);

        return app(PreventStaleDatabaseReads::class)->handle($request, $next);
    };
});

it('reads from the write connection before and after a write in an unsafe request', function () {
    $values = ($this->send)(Request::create('/entries', 'POST'), function () {
        $before = $this->db->table('markers')->value('value');
        $this->db->table('markers')->insert(['value' => 'new']);
        $after = $this->db->table('markers')->value('value');

        return [$before, $after];
    });

    expect($values)->toBe(['write', 'write']);
});

it('reads from the write connection on safe requests shortly after the session wrote data', function () {
    $read = fn () => $this->db->table('markers')->value('value');

    ($this->send)(Request::create('/entries', 'POST'), fn () => $this->db->table('markers')->insert(['value' => 'new']));
    $rightAfterWrite = ($this->send)(Request::create('/entries', 'GET'), $read);
    $this->travel(3)->seconds();
    $later = ($this->send)(Request::create('/entries', 'GET'), $read);

    expect($rightAfterWrite)->toBe('write')
        ->and($later)->toBe('read');
});

it('keeps read-only actions on the read connection', function () {
    $request = Request::create('/element-indexes/get-elements', 'POST');
    $route = new Route(['POST'], '/element-indexes/get-elements', [ElementIndexController::class, 'getElements']);
    $request->setRouteResolver(fn () => $route);

    $value = ($this->send)($request, fn () => $this->db->table('markers')->value('value'));

    expect($value)->toBe('read');
});
