<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType as EntryTypeModel;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\Http\Middleware\HandleTokenRequest;
use CraftCms\Cms\Route\ControllerRoute;
use CraftCms\Cms\Route\CurrentElement;
use CraftCms\Cms\Route\MatchedElement;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Tests\TestClasses\Route\ConfiguredEntryController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\cp_url;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', dirname(__DIR__, 3).'/Support/templates');

    app(TemplateRoots::class)->register(TemplateMode::Site, '', dirname(__DIR__, 3).'/Support/templates');

});

afterEach(function () {
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

it('dispatches matched element controller routes through the site fallback', function (string $controller) {
    $entry = createRoutableEntry('fallback-controller-route-entry', 'entries/show');

    Event::listen(function (SetRoute $event) use ($entry, $controller) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        $event->route = new ControllerRoute([$controller, 'show'], [
            'extra' => 'fallback-param',
        ]);
        $event->handled = true;
    });

    $this->get('/fallback-controller-route-entry')
        ->assertOk()
        ->assertJsonPath('elementId', $entry->id)
        ->assertJsonPath('extra', 'fallback-param');
})->with([
    'concrete method' => MatchedElementRouteTestController::class,
    'magic method' => DynamicMatchedElementRouteTestController::class,
    'custom callAction' => CallActionMatchedElementRouteTestController::class,
]);

it('dispatches a controller configured in section site settings', function (bool $registered) {
    $entry = createRoutableEntry('configured-controller', '', ConfiguredEntryController::class.'::class');

    if ($registered) {
        Route::get('/configured-controller', ConfiguredEntryController::class)->middleware(ConfiguredEntryMiddleware::class);
    }

    $response = $this->get('/configured-controller')
        ->assertOk()
        ->assertJsonPath('id', $entry->id)
        ->assertJsonPath('path', 'configured-controller');

    if ($registered) {
        $response->assertHeader('X-Configured-Route', 'executed');
    }
})->with(['unregistered' => false, 'registered' => true]);

it('injects configured entries into named closures without losing bindings or middleware', function () {
    $entry = createRoutableEntry('configured-closure', '', 'entry.closure');
    Route::bind('slug', fn (string $slug): string => strtoupper($slug));
    Route::get('/{slug}', fn (
        #[CurrentElement] ElementInterface $entry,
        string $slug,
        Request $request,
        string $suffix = 'default'
    ) => new JsonResponse(['id' => $entry->id, 'slug' => $slug, 'routeSlug' => $request->route('slug'), 'suffix' => $suffix]))->name('entry.closure')->middleware([SubstituteBindings::class, ConfiguredEntryMiddleware::class]);
    Event::listen(SetRoute::class, function (SetRoute $event) {
        throw new RuntimeException('Registered routes must not dispatch SetRoute.');
    });

    $this->get('/configured-closure')
        ->assertOk()
        ->assertHeader('X-Configured-Route', 'executed')
        ->assertJsonPath('id', $entry->id)
        ->assertJsonPath('slug', 'CONFIGURED-CLOSURE')
        ->assertJsonPath('routeSlug', 'CONFIGURED-CLOSURE')
        ->assertJsonPath('suffix', 'default');
});

it('uses the matched registration when a configured controller action has several routes', function () {
    $entry = createRoutableEntry('configured-action', '', strtoupper(ConfiguredEntryController::class).'::class::SHOW');
    Route::get('/archive/{year}/{slug}', [ConfiguredEntryController::class, 'show']);
    Route::get('/{slug}', [ConfiguredEntryController::class, 'show'])->middleware(ConfiguredEntryMiddleware::class);

    $this->get('/configured-action')
        ->assertOk()
        ->assertHeader('X-Configured-Route', 'executed')
        ->assertJsonPath('id', $entry->id)
        ->assertJsonPath('slug', 'configured-action');
});

it('keeps ordinary route handling when no eligible entry matches', function () {
    $eligible = createRoutableEntry('eligible', '', 'entry.optional');
    createRoutableEntry('other-destination', '', 'entry.other');
    $unpublished = createRoutableEntry('unpublished', '', 'entry.optional');
    DB::table(Table::ELEMENTS)->where('id', $unpublished->id)->update(['enabled' => false]);
    Route::get('/{slug}', fn (string $slug, #[CurrentElement] ?Entry $entry = null) => new JsonResponse(['slug' => $slug, 'id' => $entry?->id]))
        ->name('entry.optional');

    $this->get('/eligible')->assertOk()->assertJsonPath('slug', 'eligible')->assertJsonPath('id', $eligible->id);

    foreach (['missing', 'other-destination', 'unpublished'] as $slug) {
        $this->get("/{$slug}")->assertOk()->assertJsonPath('slug', $slug)->assertJsonPath('id', null);
    }

    Route::get('/required/{slug}', fn (#[CurrentElement] ElementInterface $entry, string $slug) => response($slug))->name('entry.required');
    $this->get('/required/missing')->assertNotFound();
});

it('resolves current elements according to parameter type and optionality', function (Closure $handler, bool $compatible, bool $optional) {
    $entry = createRoutableEntry('incompatible-element', '', 'entry.incompatible');
    Route::get('/{slug}', $handler)->name('entry.incompatible');

    $response = $this->get('/incompatible-element');

    if ($compatible || $optional) {
        $response->assertOk()->assertJsonPath('slug', 'incompatible-element')->assertJsonPath('id', $compatible ? $entry->id : null);
    } else {
        $response->assertNotFound();
    }
})->with([
    'required incompatible class' => [fn (string $slug, #[CurrentElement] Asset $element) => new JsonResponse(['slug' => $slug, 'id' => $element->id]), false, false],
    'optional incompatible class' => [fn (string $slug, #[CurrentElement] ?Asset $element = null) => new JsonResponse(['slug' => $slug, 'id' => $element?->id]), false, true],
    'required incompatible union' => [fn (string $slug, #[CurrentElement] Asset|User $element) => new JsonResponse(['slug' => $slug, 'id' => $element->id]), false, false],
    'optional incompatible union' => [fn (string $slug, #[CurrentElement] Asset|User|null $element = null) => new JsonResponse(['slug' => $slug, 'id' => $element?->id]), false, true],
    'required incompatible intersection' => [fn (string $slug, #[CurrentElement] Asset&ElementInterface $element) => new JsonResponse(['slug' => $slug, 'id' => $element->id]), false, false],
    'optional incompatible intersection' => [fn (string $slug, #[CurrentElement] (Asset&ElementInterface)|null $element = null) => new JsonResponse(['slug' => $slug, 'id' => $element?->id]), false, true],
    'compatible union' => [fn (string $slug, #[CurrentElement] Asset|Entry $element) => new JsonResponse(['slug' => $slug, 'id' => $element->id]), true, false],
    'compatible intersection in union' => [fn (string $slug, #[CurrentElement] (Entry&ElementInterface)|null $element = null) => new JsonResponse(['slug' => $slug, 'id' => $element?->id]), true, true],
]);

it('routes nested Matrix entries using their per-site destination', function () {
    $site = Site::firstOrFail();
    $type = EntryTypeModel::factory()->withFieldLayout()->create(['hasTitleField' => true]);
    $result = EntryModel::factory()->withField('routedEntries', Matrix::class, [
        'entryTypes' => [$type->id],
        'siteSettings' => [$site->uid => [
            'uriFormat' => 'nested/{slug}',
            'route' => 'entry.nested',
        ]],
    ], value: ['new1' => ['type' => $type->handle, 'slug' => 'nested-entry', 'title' => 'Nested entry']])->createElementWithFields();
    $entry = Entry::find()->ownerId($result->element->id)->one();
    Fields::saveField(Fields::getFieldById($result->fields['routedEntries']->id));
    Route::get('/nested/{slug}', fn (string $slug, #[CurrentElement] ElementInterface $entry) => new JsonResponse(['id' => $entry->id, 'slug' => $slug]))->name('entry.nested');

    $this->get('/'.$entry->uri)->assertOk()->assertJsonPath('id', $entry->id)->assertJsonPath('slug', 'nested-entry');
});

it('injects the preview element into its registered route', function () {
    $entry = createRoutableEntry('registered-preview', '', 'entry.preview');
    actingAs(UserModel::firstOrFail());
    SessionAuth::authorize("previewElement:{$entry->id}");
    $token = $this->postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => Entry::class,
        'siteId' => $entry->siteId,
        'canonicalId' => $entry->id,
    ])->assertOk()->json('token');
    Route::get('/{slug}', fn (#[CurrentElement] Entry $entry, string $slug) => new JsonResponse(['id' => $entry->id, 'previewing' => $entry->previewing]))->name('entry.preview');

    $this->get('/registered-preview?token='.$token)->assertOk()->assertJsonPath('id', $entry->id)->assertJsonPath('previewing', true);
});

it('reports destinations that are missing or do not match the entry URL', function (string $destination, string $message) {
    createRoutableEntry('invalid-destination', '', $destination);
    Route::name('entry.elsewhere')->get('/elsewhere/{slug}', [ConfiguredEntryController::class, 'show']);
    Route::get('/secured', ConfiguredEntryController::class)->middleware('auth');
    $this->withoutExceptionHandling();

    expect(fn () => $this->get('/invalid-destination'))->toThrow(InvalidArgumentException::class, $message);
})->with([
    'missing name' => ['entry.missing', 'does not exist'],
    'nonmatching name' => ['entry.elsewhere', 'does not match the entry URL'],
    'nonmatching action' => [ConfiguredEntryController::class.'@show', 'does not match the entry URL'],
    'nonmatching invokable action casing' => [strtoupper(ConfiguredEntryController::class).'@__INVOKE', 'does not match the entry URL'],
]);

it('renders matched element template routes for a full request', function () {
    $entry = createRoutableEntry('full-request-entry', 'entries/show');

    $this->get('/full-request-entry')
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:full-request-entry", escape: false);
});

it('prefers fixed routes over matched elements', function () {
    createRoutableEntry('fixed-route-entry', 'entries/show');
    createRoutableEntry('configured-elsewhere', '', 'entry.elsewhere');

    Route::middleware(['web', 'craft', 'craft.web'])
        ->get('fixed-route-entry', fn () => response('fixed-route'))->name('entry.fixed');

    DB::enableQueryLog();
    DB::flushQueryLog();

    $this->get('/fixed-route-entry')
        ->assertOk()
        ->assertSeeText('fixed-route');

    $elementQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], Table::ELEMENTS_SITES));
    DB::disableQueryLog();

    expect($elementQueries)->toBeEmpty();
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

it('allows matched element template routes with a site token during maintenance mode', function () {
    app()->maintenanceMode()->activate([]);

    $entry = createRoutableEntry('offline-site-token-entry', 'entries/show');

    $this->get('/offline-site-token-entry?'.http_build_query([
        Cms::config()->siteToken => Crypt::encrypt((string) $entry->siteId),
    ]))
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:offline-site-token-entry", escape: false);
});

it('denies matched element template routes with an empty site token during maintenance mode', function () {
    app()->maintenanceMode()->activate([]);

    createRoutableEntry('offline-empty-site-token-entry', 'entries/show');

    $this->get('/offline-empty-site-token-entry?'.Cms::config()->siteToken.'=')
        ->assertServiceUnavailable();
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

it('allows session-authenticated users with maintenance mode site access', function () {
    $entry = createRoutableEntry('offline-session-permitted-entry', 'entries/show');
    $user = UserModel::factory()
        ->withPermissions(['accessCp', 'accessSiteWhenSystemIsOff'])
        ->createElement();

    $this->post(cp_url('login'), [
        'loginName' => $user->username,
        'password' => 'password',
    ])->assertRedirect();

    $session = app(Session::class);
    $session->save();
    $this->withCookie(config('session.cookie'), $session->getId());
    app(SessionManager::class)->extend(config('session.driver'), fn () => $session->getHandler());
    app(SessionManager::class)->forgetDrivers();
    app()->forgetInstance('session.store');
    app()->forgetInstance(StartSession::class);
    auth()->forgetGuards();
    TemplateMode::set(TemplateMode::Site);
    app()->maintenanceMode()->activate([]);

    $this->get('/offline-session-permitted-entry')
        ->assertOk()
        ->assertSeeText("entry-template:{$entry->id}:offline-session-permitted-entry", escape: false);

    expect(auth()->id())->toBe($user->id);
});

function createRoutableEntry(string $uri, string $template, ?string $route = null): Entry
{
    $section = Section::factory()->create();
    $section->siteSettings()->update([
        'hasUrls' => true,
        'uriFormat' => '{slug}',
        'template' => $template,
        'route' => $route,
    ]);
    Sections::refreshSections();

    $entry = EntryModel::factory()->forSection($section)->createElement([
        'slug' => $uri,
        'title' => 'Test Entry',
    ]);

    Sections::saveSection(Sections::getSectionById($section->id), runValidation: false);

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

class DynamicMatchedElementRouteTestController
{
    /** @param list<mixed> $parameters */
    public function __call(string $method, array $parameters): JsonResponse
    {
        return new JsonResponse([
            'elementId' => $parameters[0]->id,
            'extra' => $parameters[1],
        ]);
    }
}

class CallActionMatchedElementRouteTestController extends DynamicMatchedElementRouteTestController
{
    /** @param array<string, mixed> $parameters */
    public function callAction(string $method, array $parameters): JsonResponse
    {
        return $this->__call($method, array_values($parameters));
    }
}

class ConfiguredEntryMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request)->header('X-Configured-Route', 'executed');
    }
}
