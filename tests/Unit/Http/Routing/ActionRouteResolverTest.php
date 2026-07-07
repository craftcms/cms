<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\Http\Routing\ActionRouteResolver;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Cms::config()->cpTrigger = 'admin';
    Cms::config()->actionTrigger = 'actions';
});

it('resolves site action routes from the request path', function () {
    $request = Request::create('/actions/users/login');

    $actionRoute = app(ActionRouteResolver::class)->resolve($request);

    expect($actionRoute)->toBeInstanceOf(ActionRoute::class)
        ->and($actionRoute->segments)->toBe(['users', 'login'])
        ->and($actionRoute->uri)->toBe('/actions/users/login')
        ->and($actionRoute->isCp)->toBeFalse()
        ->and($request->attributes->get(ActionRoute::class))->toBe($actionRoute);
});

it('resolves control panel action routes from the request path', function () {
    $request = Request::create('/admin/actions/users/login');

    $actionRoute = app(ActionRouteResolver::class)->resolve($request);

    expect($actionRoute)->toBeInstanceOf(ActionRoute::class)
        ->and($actionRoute->segments)->toBe(['users', 'login'])
        ->and($actionRoute->uri)->toBe('/admin/actions/users/login')
        ->and($actionRoute->isCp)->toBeTrue();
});

it('resolves root control panel action routes from the request path', function () {
    Cms::config()->cpTrigger = null;
    $request = Request::create('/actions/users/login');

    $actionRoute = app(ActionRouteResolver::class)->resolve($request);

    expect($actionRoute)->toBeInstanceOf(ActionRoute::class)
        ->and($actionRoute->segments)->toBe(['users', 'login'])
        ->and($actionRoute->uri)->toBe('/actions/users/login')
        ->and($actionRoute->isCp)->toBeTrue();
});

it('resolves action routes from the action param', function () {
    $request = Request::create('/admin/utilities/query', 'POST', [
        'action' => 'query/execute',
    ]);

    $actionRoute = app(ActionRouteResolver::class)->resolve($request);

    expect($actionRoute)->toBeInstanceOf(ActionRoute::class)
        ->and($actionRoute->segments)->toBe(['query', 'execute'])
        ->and($actionRoute->uri)->toBe('/admin/actions/query/execute')
        ->and($actionRoute->isCp)->toBeTrue();
});

it('returns null when there is no action route', function () {
    expect(app(ActionRouteResolver::class)->resolve(Request::create('/news')))->toBeNull();
});

it('returns null when the action param has no segments', function () {
    expect(app(ActionRouteResolver::class)->resolve(Request::create('/news?action=/')))->toBeNull();
});

it('aborts when the action param is not a string', function () {
    $request = Request::create('/news', 'GET', [
        'action' => ['users/login'],
    ]);

    expect(fn () => app(ActionRouteResolver::class)->resolve($request))
        ->toThrow(HttpException::class, 'Invalid action param');
});

it('builds action uris from explicit segments', function () {
    expect(ActionRoute::uriForSegments(['users', 'login'], false))->toBe('/actions/users/login')
        ->and(ActionRoute::uriForSegments(['users', 'login'], true))->toBe('/admin/actions/users/login');
});

it('builds root control panel action uris from explicit segments', function (?string $cpTrigger) {
    Cms::config()->cpTrigger = $cpTrigger;

    expect(ActionRoute::uriForSegments(['users', 'login'], true))->toBe('/actions/users/login');
})->with([
    'null' => [null],
    'slash' => ['/'],
]);
