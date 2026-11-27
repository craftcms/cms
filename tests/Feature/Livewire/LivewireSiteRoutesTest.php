<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\Livewire\LivewireComponents;
use CraftCms\Cms\Livewire\LivewireServiceProvider as CraftLivewireServiceProvider;
use CraftCms\Cms\Route\ControllerRoute;
use CraftCms\Cms\Route\Data\Route as ProjectConfigRoute;
use CraftCms\Cms\Route\ProjectConfigRoutes;
use CraftCms\Cms\Route\Routes;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Livewire\Component;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

beforeEach(function () {
    $templates = dirname(__DIR__, 2).'/Support/templates';

    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', $templates);
    app(TemplateRoots::class)->register(TemplateMode::Site, '', $templates);

    Livewire::addNamespace(LivewireComponents::NAMESPACE, viewPath: $templates);
    view()->addLocation($templates);
    view()->addNamespace('layouts', $templates.'/livewire/_layouts');
});

it('renders a single-file component section template as a full page', function () {
    $entry = createLivewireEntry('lw-page', 'livewire/entry.blade.php');

    $response = $this->get('/lw-page')->assertOk();

    expect($response->getContent())
        ->toContain('<body>layout:')
        ->toContain("entry:Livewire Entry:{$entry->id}:likes:0:site:default")
        // Only declared variables reach the component, so nothing leaks into its root element as an attribute.
        ->not->toContain('entry="');

    livewireCall($this, $response, 'like')
        ->assertOk()
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, "entry:Livewire Entry:{$entry->id}:likes:1"));
});

it('still renders plain Blade section templates as views', function () {
    createLivewireEntry('lw-plain', 'livewire/plain.blade.php');

    $this->get('/lw-plain')
        ->assertOk()
        ->assertSee('plain:Livewire Entry')
        ->assertDontSee('wire:snapshot', escape: false);
});

it('mounts Livewire class components used as controller routes', function () {
    $entry = createLivewireEntry('lw-class', 'entries/show');

    Event::listen(function (SetRoute $event) {
        $event->route = new ControllerRoute(LivewireRouteTestComponent::class);
        $event->handled = true;
    });

    $response = $this->get('/lw-class')->assertOk();

    expect($response->getContent())
        ->toContain('<title>Livewire Entry</title>')
        ->toContain("class:{$entry->id}:{$entry->id}:0");

    livewireCall($this, $response, 'like')
        ->assertOk()
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, "class:{$entry->id}:{$entry->id}:1"));
});

it('embeds components in Twig templates', function () {
    $entry = createLivewireEntry('lw-twig', 'livewire/embed');

    $response = $this->get('/lw-twig')->assertOk();

    expect($response->getContent())
        ->toStartWith('twig:<')
        ->toContain("entry:Livewire Entry:{$entry->id}:likes:0")
        ->not->toContain('<body>layout:');
});

it('keeps update requests on the site the component was rendered for', function () {
    Site::factory()->create(['handle' => 'french', 'baseUrl' => 'https://localhost/fr']);
    Sites::refreshSites();
    app(Routes::class)->saveRoute(new ProjectConfigRoute(
        uriParts: ['lw-counter'],
        template: 'livewire/counter',
        siteUid: Sites::getSiteByHandle('french')->uid,
    ));
    app(ProjectConfigRoutes::class)->register();

    $response = $this->get('https://localhost/fr/lw-counter')->assertOk();
    expect($response->getContent())->toContain('<body>layout:')->toContain('count:0:site:french');

    // The update endpoint is at the host root, which belongs to the primary site, and the client’s site header is ignored.
    livewireCall($this, $response, 'like', ['X-Craft-Site' => (string) Sites::getPrimarySite()->id])
        ->assertOk()
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, 'count:1:site:french'));
});

it('keeps previews across update requests', function () {
    $entry = createLivewireEntry('lw-preview', 'livewire/entry.blade.php');
    $draft = app(Drafts::class)->createDraft($entry, 1);
    $draft->title = 'Draft title';
    app(Elements::class)->saveElement($draft, runValidation: false);

    $token = app(RouteTokens::class)->createPreviewToken([ActionRoute::uriForSegments(['preview', 'preview'], false), [
        'elementType' => Entry::class,
        'canonicalId' => $entry->id,
        'siteId' => $entry->siteId,
        'draftId' => $draft->draftId,
    ]]);

    $response = $this->get('/lw-preview?'.http_build_query([
        Cms::config()->tokenParam => $token,
        'x-craft-preview' => Crypt::encrypt('preview'),
    ]))->assertOk();

    expect($response->getContent())->toContain('entry:Draft title:')->toContain(':likes:0:')->toContain(':preview:1');

    // The update is a preview request too, so caches skip it and drafts stay previewed.
    livewireCall($this, $response, 'like')
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, private')
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, 'entry:Draft title:')
            && str_contains($html, ':likes:1:') && str_contains($html, ':preview:1'));
});

it('keeps the response a page component sets for section templates', function () {
    createLivewireEntry('lw-status', 'livewire/status.blade.php');

    $this->get('/lw-status')
        ->assertStatus(202)
        ->assertHeader('X-Livewire-Page', 'status')
        ->assertCookie('livewire-page', 'status')
        ->assertSee('status');
});

it('applies Craft’s middleware only to updates for components rendered on Craft routes', function () {
    // Craft’s `craft` middleware group adds this header, so it shows whether the group ran.
    Cms::config()->disallowRobots();
    Route::middleware('web')->get('lw-app-page', fn () => '<html><body>'.Livewire::mount(LivewireAppTestComponent::class).'</body></html>');

    $appPage = $this->get('/lw-app-page')->assertOk()->assertHeaderMissing('X-Robots-Tag');

    livewireCall($this, $appPage, 'increment')
        ->assertOk()
        ->assertHeaderMissing('X-Robots-Tag')
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, 'app:1'));

    createLivewireEntry('lw-craft-page', 'livewire/entry.blade.php');
    $craftPage = $this->get('/lw-craft-page')->assertOk()->assertHeader('X-Robots-Tag', 'none');

    livewireCall($this, $craftPage, 'like')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'none')
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, 'likes:1'));

    // A Craft component bundled after one of the app’s can’t skip Craft’s middleware.
    $this->withHeaders(['X-Livewire' => '1'])->postJson(EndpointResolver::updatePath(), [
        '_token' => csrf_token(),
        'components' => [livewireComponentCall($appPage, 'increment'), livewireComponentCall($craftPage, 'like')],
    ])->assertStatus(400);
});

it('applies Craft’s middleware on custom update routes', function () {
    Cms::config()->disallowRobots();
    Livewire::setUpdateRoute(fn ($handle) => Route::post('lw-custom/update', $handle));
    // The app has already booted, so this adds the middleware to the new route right away.
    new CraftLivewireServiceProvider(app())->boot();

    createLivewireEntry('lw-custom-page', 'livewire/entry.blade.php');
    $page = $this->get('/lw-custom-page')->assertOk();

    livewireCall($this, $page, 'like', uri: '/lw-custom/update')
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'none')
        ->assertJsonPath('components.0.effects.html', fn (string $html) => str_contains($html, 'likes:1'));
});

/**
 * @param  array<string, string>  $headers
 */
function livewireCall($test, TestResponse $page, string $method, array $headers = [], ?string $uri = null): TestResponse
{
    return $test->withHeaders(['X-Livewire' => '1', ...$headers])->postJson($uri ?? EndpointResolver::updatePath(), [
        '_token' => csrf_token(),
        'components' => [livewireComponentCall($page, $method)],
    ]);
}

/**
 * @return array<string, mixed>
 */
function livewireComponentCall(TestResponse $page, string $method): array
{
    preg_match('/wire:snapshot="([^"]+)"/', (string) $page->getContent(), $matches);

    return [
        'snapshot' => html_entity_decode($matches[1]),
        'updates' => new stdClass,
        'calls' => [['path' => '', 'method' => $method, 'params' => []]],
    ];
}

function createLivewireEntry(string $uri, string $template): Entry
{
    $section = Section::factory()->create();
    $section->siteSettings()->update([
        'hasUrls' => true,
        'uriFormat' => '{slug}',
        'template' => $template,
    ]);
    Sections::refreshSections();

    $entry = EntryModel::factory()->forSection($section)->createElement([
        'slug' => $uri,
        'title' => 'Livewire Entry',
    ]);

    Sections::saveSection(Sections::getSectionById($section->id), runValidation: false);

    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $entry->id)
        ->update(['uri' => $uri, 'slug' => $uri, 'title' => 'Livewire Entry']);

    return Entry::find()->id($entry->id)->one();
}

class LivewireAppTestComponent extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function render(): string
    {
        return '<div>app:{{ $count }}</div>';
    }
}

class LivewireRouteTestComponent extends Component
{
    public Entry $entry;

    public int $elementId;

    public int $likes = 0;

    public function mount(Entry $element): void
    {
        $this->elementId = $element->id;
    }

    public function like(): void
    {
        $this->likes++;
    }

    public function rendering($view): void
    {
        $view->title($this->entry->title);
    }

    public function render(): string
    {
        return '<div>class:{{ $entry->id }}:{{ $elementId }}:{{ $likes }}</div>';
    }
}
