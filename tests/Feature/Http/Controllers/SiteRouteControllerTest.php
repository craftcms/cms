<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Route\ControllerRoute;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app()->maintenanceMode()->deactivate();
    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', dirname(__DIR__, 3).'/Support/templates');

    app(TemplateRoots::class)->register(TemplateMode::Site, '', dirname(__DIR__, 3).'/Support/templates');

});

afterEach(function () {
    app()->maintenanceMode()->deactivate();
    Context::forgetHidden(HandleTokenRequest::HAD_TOKEN_KEY);
});

it('dispatches matched element invokable controller routes from the set route event', function () {
    $entry = createRoutableEntry('invokable-controller-route-entry', 'entries/show');

    Event::listen(function (SetRoute $event) {
        $event->route = new ControllerRoute(InvokableMatchedElementRouteTestController::class);
        $event->handled = true;
    });

    $this->get('/invokable-controller-route-entry')
        ->assertOk()
        ->assertJsonPath('elementId', $entry->id)
        ->assertJsonPath('path', 'invokable-controller-route-entry');
});

it('dispatches matched element controller routes through the site fallback', function () {
    $entry = createRoutableEntry('fallback-controller-route-entry', 'entries/show');

    Event::listen(function (SetRoute $event) use ($entry) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        $event->route = new ControllerRoute([MatchedElementRouteTestController::class, 'show'], [
            'extra' => 'fallback-param',
        ]);
        $event->handled = true;
    });

    $this->get('/fallback-controller-route-entry')
        ->assertOk()
        ->assertJsonPath('elementId', $entry->id)
        ->assertJsonPath('extra', 'fallback-param');
});

it('renders matched element template routes for a full request', function () {
    $entry = createRoutableEntry('full-request-entry', 'entries/show');

    $this->get('/full-request-entry')
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:full-request-entry", escape: false);
});

it('prefers fixed routes over matched elements', function () {
    createRoutableEntry('fixed-route-entry', 'entries/show');

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('fixed-route-entry', fn () => response('fixed-route'));

    $this->get('/fixed-route-entry')
        ->assertOk()
        ->assertSeeText('fixed-route');
});

it('accepts POST requests to the site fallback instead of throwing a method not allowed exception', function () {
    $this->post('/some-unmatched-path')
        ->assertNotFound();
});

it('keeps matched element state out of dehydrated queue context', function () {
    $entry = createRoutableEntry('dehydrated-context-entry', 'entries/show');

    $this->get('/dehydrated-context-entry')->assertOk();

    expect(MatchedElement::get()->id)->toBe($entry->id)
        ->and(fn () => Context::dehydrate())->not->toThrow(Throwable::class);
});

it('denies anonymous matched element template routes during maintenance mode', function () {
    app()->maintenanceMode()->activate([]);

    createRoutableEntry('offline-entry', 'entries/show');

    $this->get('/offline-entry')
        ->assertServiceUnavailable();
});

it('denies anonymous homepage requests during maintenance mode', function () {
    app()->maintenanceMode()->activate(['retry' => 60]);

    $this->get('/')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '60');
});

it('allows matched element template routes with a site token during maintenance mode', function () {
    app()->maintenanceMode()->activate([]);

    $entry = createRoutableEntry('offline-site-token-entry', 'entries/show');

    $this->get('/offline-site-token-entry?'.http_build_query([
        Cms::config()->siteToken => Crypt::encrypt((string) $entry->siteId),
    ]))
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:offline-site-token-entry", escape: false);
});

it('allows matched element template routes with a valid route token during maintenance mode', function () {
    app()->maintenanceMode()->activate([]);

    $entry = createRoutableEntry('offline-route-token-entry', 'entries/show');

    $token = app(RouteTokens::class)->createToken('/offline-route-token-entry');

    $this->get("/offline-route-token-entry?token={$token}")
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:offline-route-token-entry", escape: false);
});

it('denies matched element template routes for users without maintenance mode site access', function () {
    app()->maintenanceMode()->activate([]);

    createRoutableEntry('offline-unpermitted-entry', 'entries/show');
    $user = UserModel::factory()->createElement();

    actingAs($user);

    $this->get('/offline-unpermitted-entry')
        ->assertServiceUnavailable();
});

it('allows matched element template routes for users with maintenance mode site access', function () {
    app()->maintenanceMode()->activate([]);

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

class MatchedElementRouteTestController
{
    public function show(ElementInterface $element, Request $request, string $extra = ''): JsonResponse
    {
        return new JsonResponse([
            'elementId' => $element->id,
            'matchedElementId' => MatchedElement::get()->id,
            'path' => $request->path(),
            'extra' => $extra,
        ]);
    }
}

class InvokableMatchedElementRouteTestController
{
    public function __invoke(ElementInterface $element, Request $request): JsonResponse
    {
        return new JsonResponse([
            'elementId' => $element->id,
            'path' => $request->path(),
        ]);
    }
}
