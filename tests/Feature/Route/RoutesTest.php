<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Exceptions\InvalidRouteException;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Site\Exceptions\SiteNotFoundException;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;

beforeEach(function () {
    $this->routes = app(Routes::class);
    $this->projectConfig = app(ProjectConfig::class);

    // Make sure migrations are run
    Site::first();
});

it('can get project config routes', function () {
    expect($this->routes->getProjectConfigRoutes())->toBeEmpty();

    $this->routes->saveRoute(new Route(
        uriParts: ['foo'],
        template: 'foo',
    ));

    expect($this->routes->getProjectConfigRoutes())->not()->toBeEmpty();
});

it('can save supported routes', function (array $expected, array $uriParts) {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: $uriParts,
        template: '_test',
    ));

    expect($uid)->toBeUuid();
    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid))->toBe($expected);
})->with([
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
        ],
        [],
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => ['test1', 'test2'],
        ],
        ['test1', 'test2'],
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => [['archiveDate', '(?:19|20)\d{2}-(?:0[1-9]|1[0-2])']],
        ],
        [['archiveDate', '(?:19|20)\d{2}-(?:0[1-9]|1[0-2])']],
    ],
]);

it('normalizes empty URI parts before saving', function () {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['', 'news/', '', ['slug', '[^\\/]+'], ''],
        template: '_test',
    ));

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid.'.uriParts'))
        ->toBe(['news/', ['slug', '[^\\/]+']]);
});

it('rejects invalid routes without changing project config', function (Route $route, string $errorKey) {
    $configBefore = $this->projectConfig->get(ProjectConfig::PATH_ROUTES);

    try {
        $this->routes->saveRoute($route);
        test()->fail('Expected the invalid route to be rejected.');
    } catch (InvalidRouteException $exception) {
        expect($exception->errors())->toHaveKey($errorKey);
    }

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES))->toBe($configBefore);
})->with([
    'URI parts must be a list' => [
        new Route(uriParts: [1 => 'news'], template: '_test'),
        'uriParts',
    ],
    'tuples require a regex' => [
        new Route(uriParts: [['slug']], template: '_test'),
        'uriParts',
    ],
    'tuples cannot contain extra values' => [
        new Route(uriParts: [['slug', '[^\\/]+', 'extra']], template: '_test'),
        'uriParts',
    ],
    'tuple values must be strings' => [
        new Route(uriParts: [['slug', 1]], template: '_test'),
        'uriParts',
    ],
    'template cannot be empty' => [
        new Route(uriParts: ['news'], template: ''),
        'template',
    ],
    'site must exist' => [
        new Route(
            uriParts: ['news'],
            template: '_test',
            siteUid: '11111111-1111-4111-8111-111111111111',
        ),
        'siteUid',
    ],
    'action trigger is reserved' => [
        new Route(uriParts: ['actions/news'], template: '_test'),
        'uriParts',
    ],
    'control panel trigger is reserved' => [
        new Route(uriParts: ['admin/news'], template: '_test'),
        'uriParts',
    ],
]);

it('can delete a route by uid', function () {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['foo'],
        template: 'foo',
    ));

    expect($this->routes->getProjectConfigRoutes())->not()->toBeEmpty();

    $this->routes->deleteRouteByUid($uid);

    expect($this->routes->getProjectConfigRoutes())->toBeEmpty();
});

it('returns no project config routes when no current site exists yet', function () {
    $sites = mock(Sites::class);
    $sites->shouldReceive('getCurrentSite')->andThrow(new SiteNotFoundException('No primary site exists'));

    $routes = new Routes($this->projectConfig, $sites);

    expect($routes->getProjectConfigRoutes())->toBeEmpty();
});
