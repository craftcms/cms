<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\SetRoute;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Middleware\HandleMatchedElementRoute;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\View\Events\RegisterSiteTemplateRoots;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    $this->middleware = app(HandleMatchedElementRoute::class);
    TemplateMode::set(TemplateMode::Site);
    Aliases::set('@templates', dirname(__DIR__, 3).'/Support/templates');

    Event::listen(function (RegisterSiteTemplateRoots $event) {
        $event->roots[''] = dirname(__DIR__, 3).'/Support/templates';
    });

    Route::middleware(['web', 'craft'])->any('actions/custom/route', fn () => new JsonResponse([
        'matchedElementId' => Context::getHidden('craft.matchedElement')?->id,
    ]));

    Route::middleware(['web', 'craft', 'craft.web'])->any('{path?}', fn () => new JsonResponse([
        'fallback' => true,
        'path' => request()->path(),
    ]))->where('path', '.*');
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
        ->and(Context::getHidden('craft.matchedElement')?->id)->toBe($entry->id);
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
