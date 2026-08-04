<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Settings\RoutesController;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\Site\Models\Site;
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
    $uid = $this->routes->saveRoute(new Route(
        uriParts: ['news'],
        template: 'news/_index',
    ));

    Cms::config()->allowAdminChanges = false;

    get(action([RoutesController::class, 'index']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/Index')
            ->where('readOnly', true));

    get(action([RoutesController::class, 'edit'], ['uid' => $uid]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/Edit')
            ->where('route.uid', $uid)
            ->where('readOnly', true));

    get(action([RoutesController::class, 'create']))->assertForbidden();
    post(action([RoutesController::class, 'store']))->assertForbidden();
    patch(action([RoutesController::class, 'update'], ['uid' => $uid]))->assertForbidden();
    delete(action([RoutesController::class, 'destroy'], ['uid' => $uid]))->assertForbidden();
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
            ->component('settings/routes/Index')
            ->where('title', 'Routes')
            ->where('routes', fn ($routes): bool => collect($routes)->contains(fn (array $route): bool => $route['uid'] === $uid
                && $route['siteUid'] === $siteUid
                && $route['uriParts'] === ['news/', ['slug', '[^\/]+']]
                && $route['template'] === 'news/_entry'))
            ->where('readOnly', false));
});

it('can show the create route screen', function () {
    get(action([RoutesController::class, 'create']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/routes/Edit')
            ->where('title', 'Create a new route')
            ->where('route.uid', null)
            ->where('route.siteUid', null)
            ->where('route.uriParts', [''])
            ->where('route.template', '')
            ->where('tokens', fn ($tokens): bool => routeOptionsContain($tokens, 'year', '\d{4}'))
            ->where('sites', fn ($sites): bool => routeOptionsContain($sites, 'Global', ''))
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
            ->component('settings/routes/Edit')
            ->where('title', 'Edit Route')
            ->where('route.uid', $uid)
            ->where('route.siteUid', $siteUid)
            ->where('route.uriParts.0', 'news/')
            ->where('route.uriParts.1.0', 'slug')
            ->where('route.template', 'news/_entry')
            ->where('tokens', fn ($tokens): bool => routeOptionsContain($tokens, 'year', '\d{4}'))
            ->where('sites', fn ($sites): bool => routeOptionsContain($sites, 'Global', ''))
            ->where('readOnly', false));
});

it('can create a route', function (array $uriParts, ?array $expectedUriParts) {
    post(action([RoutesController::class, 'store']), [
        'uriParts' => $uriParts,
        'template' => '_route',
        'siteUid' => null,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $uid = $this->routes->getProjectConfigRoutes()->where('template', '_route')->first()->uid;
    $config = $this->projectConfig->get(ProjectConfig::PATH_ROUTES.'.'.$uid);

    expect($config['template'])->toBe('_route')
        ->and($config['siteUid'])->toBeNull()
        ->and($config['uriParts'] ?? null)->toBe($expectedUriParts);
})->with([
    'empty uri' => [
        'uriParts' => [],
        'expectedUriParts' => null,
    ],
    'plain uri' => [
        'uriParts' => ['news'],
        'expectedUriParts' => ['news'],
    ],
    'token uri' => [
        'uriParts' => ['news/', ['slug', '[^\/]+']],
        'expectedUriParts' => ['news/', ['slug', '[^\/]+']],
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

function routeOptionsContain(mixed $options, string $label, string $value): bool
{
    return collect($options)
        ->contains(fn (array $option): bool => $option['label'] === $label && $option['value'] === $value);
}
