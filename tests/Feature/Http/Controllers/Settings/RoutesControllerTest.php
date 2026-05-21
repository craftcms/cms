<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->routes = app(Routes::class);
    $this->projectConfig = app(ProjectConfig::class);

    Site::first();
});

it('requires authentication', function () {
    Auth::logout();

    get(action([RoutesController::class, 'index']))->assertRedirect();
    get(action([RoutesController::class, 'create']))->assertRedirect();
    get(action([RoutesController::class, 'edit'], ['uid' => '11111111-1111-4111-8111-111111111111']))->assertRedirect();
    post(action([RoutesController::class, 'store']))->assertRedirect();
    patch(action([RoutesController::class, 'update'], ['uid' => '11111111-1111-4111-8111-111111111111']))->assertRedirect();
    delete(action([RoutesController::class, 'destroy'], ['uid' => '11111111-1111-4111-8111-111111111111']))->assertRedirect();
    post(action([RoutesController::class, 'reorder']))->assertRedirect();
});

it('requires admin changes for mutations', function () {
    Cms::config()->allowAdminChanges = false;

    get(action([RoutesController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/RoutesIndexPage')
            ->where('readOnly', true));

    get(action([RoutesController::class, 'create']))->assertForbidden();
    post(action([RoutesController::class, 'store']))->assertForbidden();
    patch(action([RoutesController::class, 'update'], ['uid' => '11111111-1111-4111-8111-111111111111']))->assertForbidden();
    delete(action([RoutesController::class, 'destroy'], ['uid' => '11111111-1111-4111-8111-111111111111']))->assertForbidden();
    post(action([RoutesController::class, 'reorder']))->assertForbidden();
});

it('can show the routes screen', function () {
    $siteUid = Site::first()->uid;
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['news/', ['slug', '[^\/]+']],
        template: 'news/_entry',
        siteUid: $siteUid,
    ));

    get(action([RoutesController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/RoutesIndexPage')
            ->where('title', 'Routes')
            ->where('routes.0.uid', $uid)
            ->where('routes.0.siteUid', $siteUid)
            ->where('routes.0.uriParts.0', 'news/')
            ->where('routes.0.uriParts.1.0', 'slug')
            ->where('routes.0.template', 'news/_entry')
            ->missing('sites')
            ->missing('tokens')
            ->missing('actionTrigger')
            ->missing('cpTrigger')
            ->where('readOnly', false));
});

it('can show the create route screen', function () {
    get(action([RoutesController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/EditRoutePage')
            ->where('title', 'Create a new route')
            ->where('route.uid', null)
            ->where('route.siteUid', null)
            ->where('route.uriParts', [''])
            ->where('route.template', '')
            ->where('tokens', fn ($tokens): bool => collect($tokens)
                ->contains(fn (array $token): bool => $token['name'] === 'year' && $token['value'] === '\d{4}'))
            ->where('sites.0.value', '')
            ->where('sites.0.label', 'Global')
            ->where('actionMenu', null)
            ->where('actionMenuItems', null)
            ->where('readOnly', false));
});

it('can show the edit route screen', function () {
    $siteUid = Site::first()->uid;
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['news/', ['slug', '[^\/]+']],
        template: 'news/_entry',
        siteUid: $siteUid,
    ));

    get(action([RoutesController::class, 'edit'], ['uid' => $uid]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/EditRoutePage')
            ->where('title', 'Edit Route')
            ->where('route.uid', $uid)
            ->where('route.siteUid', $siteUid)
            ->where('route.uriParts.0', 'news/')
            ->where('route.uriParts.1.0', 'slug')
            ->where('route.template', 'news/_entry')
            ->where('tokens', fn ($tokens): bool => collect($tokens)
                ->contains(fn (array $token): bool => $token['name'] === 'year' && $token['value'] === '\d{4}'))
            ->where('sites.0.value', '')
            ->where('sites.0.label', 'Global')
            ->where('actionMenuItems.0.label', 'Delete')
            ->where('actionMenuItems.0.icon', 'trash')
            ->where('actionMenuItems.0.destructive', true)
            ->where('actionMenuItems.0.type', 'button')
            ->where('actionMenuItems.0.attributes.data.route-delete-action', true)
            ->where('actionMenuItems.0.attributes.data.route-delete-url', Url::cpUrl("settings/routes/$uid"))
            ->where('readOnly', false));
});

it('can show the edit route screen in read-only mode', function () {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['news'],
        template: 'news/_index',
    ));

    Cms::config()->allowAdminChanges = false;

    get(action([RoutesController::class, 'edit'], ['uid' => $uid]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/EditRoutePage')
            ->where('route.uid', $uid)
            ->where('actionMenu', null)
            ->where('actionMenuItems', null)
            ->where('readOnly', true));
});

it('can create a route', function (array $uriParts, array $expected) {
    post(action([RoutesController::class, 'store']), [
        'uriParts' => $uriParts,
        'template' => '_route',
        'siteUid' => null,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $uid = $this->routes->getProjectConfigRoutes()->where('template', '_route')->first()->uid;

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid))->toBe($expected);
})->with([
    'empty uri' => [
        'uriParts' => [],
        'expected' => [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_route',
        ],
    ],
    'plain uri' => [
        'uriParts' => ['news'],
        'expected' => [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_route',
            'uriParts' => ['news'],
        ],
    ],
    'token uri' => [
        'uriParts' => ['news/', ['slug', '[^\/]+']],
        'expected' => [
            'siteUid' => null,
            'sortOrder' => 1,
            'template' => '_route',
            'uriParts' => ['news/', ['slug', '[^\/]+']],
        ],
    ],
]);

it('can create a site-specific route', function () {
    $siteUid = Site::first()->uid;

    post(action([RoutesController::class, 'store']), [
        'uriParts' => ['news'],
        'template' => '_route',
        'siteUid' => $siteUid,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    $uid = $this->routes->getProjectConfigRoutes()->where('template', '_route')->first()->uid;

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid.'.siteUid'))->toBe($siteUid);
});

it('can update a route', function () {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['old'],
        template: 'old',
    ));

    patch(action([RoutesController::class, 'update'], ['uid' => $uid]), [
        'uriParts' => ['new/', ['year', '\d{4}']],
        'template' => 'new',
        'siteUid' => null,
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid.'.uriParts'))->toBe(['new/', ['year', '\d{4}']])
        ->and($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid.'.template'))->toBe('new');
});

it('can delete a route', function () {
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['old'],
        template: 'old',
    ));

    delete(action([RoutesController::class, 'destroy'], ['uid' => $uid]))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid))->toBeNull();
});

it('can reorder routes', function () {
    $firstUid = $this->routes->saveRoute(new Route(
        uriParts: ['first'],
        template: 'first',
    ));
    $secondUid = $this->routes->saveRoute(new Route(
        uriParts: ['second'],
        template: 'second',
    ));

    post(action([RoutesController::class, 'reorder']), [
        'routeUids' => [$secondUid, $firstUid],
    ])->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$secondUid.'.sortOrder'))->toBe(1)
        ->and($this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$firstUid.'.sortOrder'))->toBe(2);
});

it('validates route uri parts', function () {
    post(action([RoutesController::class, 'store']), [
        'uriParts' => [['slug']],
        'template' => '_route',
    ])->assertSessionHasErrors('uriParts');
});

it('validates route uris do not start with reserved triggers', function (array $uriParts) {
    post(action([RoutesController::class, 'store']), [
        'uriParts' => $uriParts,
        'template' => '_route',
    ])->assertSessionHasErrors('uriParts');
})->with([
    'action trigger' => [['actions/foo']],
    'cp trigger' => [['admin/foo']],
]);
