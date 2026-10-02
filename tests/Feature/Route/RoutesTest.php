<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Data\Route;
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

it('can save routes', function (array $expected, array $uriParts, string $template, ?string $siteUid = null) {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: $uriParts,
        template: $template,
        siteUid: $siteUid,
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
        [], '_test',
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => ['test1', 'test2'],
        ],
        ['test1', 'test2'], '_test',
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => [['validHandle', 'date'], ['someHandle', 'slug']],
        ],
        [['validHandle', 'date'], ['someHandle', 'slug']], '_test',
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']],
        ],
        [['validHandle', 'date'], ['!@#$%^&*(', 'validHandle'], ['validHandle', '!@#$%^&*(']], '_test',
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']],
        ],
        [['validHandle', 'date', 'extraParamThatIsntUsed'], ['!@#$%^&*(', 'validHandle']], '_test',
    ],
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => [['validHandle', 'date'], 'noArray'],
        ],
        [['validHandle', 'date'], 'noArray'], '_test',
    ],

    // TODO: Well more a question. Shouldn't emojis (UTF-8) be allowed in routes?
    [
        [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_test',
            'uriParts' => [['😎', 'date'], ['😎', 'emoji']],
        ],
        [['😎', 'date'], ['😎', 'emoji']], '_test',
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
