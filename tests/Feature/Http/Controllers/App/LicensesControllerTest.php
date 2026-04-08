<?php

declare(strict_types=1);

use CraftCms\Cms\Http\Controllers\App\LicensesController;
use CraftCms\Cms\License\License;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('set license shun cookie validates required hash', function () {
    postJson(action([LicensesController::class, 'setShunCookie']))
        ->assertJsonValidationErrors(['hash']);
});

test('set license shun cookie queues count and hash', function () {
    $cookieName = app(License::class)->shunCookieName();

    $request = Request::create('/', 'POST', [
        'hash' => 'new-hash',
    ]);
    $request->headers->set('Accept', 'application/json');
    $request->headers->set('X-Requested-With', 'XMLHttpRequest');
    $request->cookies->set($cookieName, json_encode([
        'hash' => 'old-hash',
        'timestamp' => '2025-01-01T00:00:00+00:00',
        'count' => 2,
    ], JSON_THROW_ON_ERROR));

    app()->instance('request', $request);
    app()->forgetScopedInstances();

    $response = app(LicensesController::class)->setShunCookie($request, app(License::class));

    expect($response->getStatusCode())->toBe(200);

    $cookie = Cookie::queued($cookieName);

    expect(Cookie::hasQueued($cookieName))->toBeTrue()
        ->and($cookie)->not()->toBeNull();

    $data = json_decode((string) $cookie->getValue(), true, flags: JSON_THROW_ON_ERROR);

    expect($data['hash'])->toBe('new-hash')
        ->and($data['count'])->toBe(3)
        ->and($data['timestamp'])->toBeString();
});
