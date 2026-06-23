<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Middleware\HandleMatchedElementRoute;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\Events\SiteTemplateRootsResolving;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Cms::config()->isSystemLive = true;
    $this->middleware = app(HandleMatchedElementRoute::class);
    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', dirname(__DIR__, 3).'/Support/templates');

    Event::listen(function (SiteTemplateRootsResolving $event) {
        $event->roots[''] = dirname(__DIR__, 3).'/Support/templates';
    });

    Route::middleware(['web', 'craft'])->any('actions/custom/route', fn () => new JsonResponse([
        'matchedElementId' => MatchedElement::get()->id,
    ]));

    Route::middleware(['web', 'craft', 'craft.web'])->any('{path?}', fn () => new JsonResponse([
        'fallback' => true,
        'path' => request()->path(),
    ]))->where('path', '.*');
});

afterEach(function () {
    Cms::config()->isSystemLive = null;
    Context::forgetHidden(HandleTokenRequest::HAD_TOKEN_KEY);
});

it('does nothing when no element matches the request uri', function () {
    $request = Request::create('/missing');
    app()->instance('request', $request);

    $response = $this->middleware->handle($request, fn () => response('next'));

    expect($response->getContent())->toBe('next');
});

it('renders matched element template routes directly', function () {
    $entry = createRoutableEntry('test-entry', 'entries/show');

    $request = Request::create('/test-entry');
    app()->instance('request', $request);

    $response = $this->middleware->handle($request, fn () => response('next'));

    expect($response->getStatusCode())->toBe(200)
        ->and(trim((string) $response->getContent()))->toBe("entry-template:{$entry->id}:test-entry")
        ->and(MatchedElement::get()->id)->toBe($entry->id);
});

it('dispatches matched element string routes through an action request', function () {
    $entry = createRoutableEntry('custom-route-entry', 'entries/show');

    Event::listen(function (SetRoute $event) use ($entry) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        $event->route = 'custom/route';
        $event->handled = true;
    });

    $request = Request::create('/custom-route-entry');
    app()->instance('request', $request);

    $response = $this->middleware->handle($request, fn () => response('next'));

    expect($response->getStatusCode())->toBe(200)
        ->and(json_decode((string) $response->getContent(), true))->toMatchArray([
            'matchedElementId' => $entry->id,
        ]);
});

it('renders matched element template routes for a full request', function () {
    $entry = createRoutableEntry('full-request-entry', 'entries/show');

    $this->get('/full-request-entry')
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:full-request-entry", escape: false);
});

it('keeps matched element state out of dehydrated queue context', function () {
    $entry = createRoutableEntry('dehydrated-context-entry', 'entries/show');

    $request = Request::create('/dehydrated-context-entry');
    app()->instance('request', $request);

    $response = $this->middleware->handle($request, fn () => response('next'));

    expect($response->getStatusCode())->toBe(200)
        ->and(MatchedElement::get()->id)->toBe($entry->id)
        ->and(fn () => Context::dehydrate())->not->toThrow(Throwable::class);
});

it('denies anonymous matched element template routes when the system is offline', function () {
    Cms::config()->isSystemLive = false;

    createRoutableEntry('offline-entry', 'entries/show');

    $this->get('/offline-entry')
        ->assertServiceUnavailable();
});

it('denies anonymous homepage requests when the system is offline', function () {
    Cms::config()->isSystemLive = false;

    $this->get('/')
        ->assertServiceUnavailable();
});

it('allows matched element template routes with a site token when the system is offline', function () {
    Cms::config()->isSystemLive = false;

    $entry = createRoutableEntry('offline-site-token-entry', 'entries/show');

    $this->get('/offline-site-token-entry?'.http_build_query([
        Cms::config()->siteToken => Crypt::encrypt((string) $entry->siteId),
    ]))
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:offline-site-token-entry", escape: false);
});

it('allows matched element template routes with a valid route token when the system is offline', function () {
    Cms::config()->isSystemLive = false;

    $entry = createRoutableEntry('offline-route-token-entry', 'entries/show');

    Context::addHidden(HandleTokenRequest::HAD_TOKEN_KEY, true);

    $request = Request::create('/offline-route-token-entry');
    app()->instance('request', $request);

    $response = $this->middleware->handle($request, fn () => response('next'));

    expect($response->getStatusCode())->toBe(200)
        ->and(trim((string) $response->getContent()))->toBe("entry-template:{$entry->id}:offline-route-token-entry");
});

it('denies matched element template routes for users without offline site access', function () {
    Cms::config()->isSystemLive = false;

    createRoutableEntry('offline-unpermitted-entry', 'entries/show');
    $user = UserModel::factory()->createElement();

    actingAs($user);

    $this->get('/offline-unpermitted-entry')
        ->assertServiceUnavailable();
});

it('allows matched element template routes for users with offline site access', function () {
    Cms::config()->isSystemLive = false;

    $entry = createRoutableEntry('offline-permitted-entry', 'entries/show');
    $user = UserModel::factory()
        ->withPermissions(['accessSiteWhenSystemIsOff'])
        ->createElement();

    actingAs($user);

    $this->get('/offline-permitted-entry')
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:offline-permitted-entry", escape: false);
});

function createRoutableEntry(string $uri, string $template): Entry
{
    $section = Section::factory()->create();
    $section->siteSettings()->update([
        'hasUrls' => true,
        'uriFormat' => '{slug}',
        'template' => $template,
    ]);

    $entry = EntryModel::factory()->forSection($section)->createElement([
        'slug' => $uri,
        'title' => 'Test Entry',
    ]);

    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->update([
            'uri' => $uri,
            'slug' => $uri,
            'title' => 'Test Entry',
        ]);

    return Entry::find()->id($entry->id)->one();
}
