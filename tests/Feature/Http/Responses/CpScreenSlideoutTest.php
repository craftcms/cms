<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Settings\EntryTypesController;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

beforeEach(function () {
    actingAs(User::find()->one());

    EntryType::factory()->create();
});

function editUrl(): string
{
    return action([EntryTypesController::class, 'edit'], [EntryType::first()->id]);
}

/**
 * A slideout request is any request a CP screen sees as JSON — the convention
 * Craft 5 established and Craft 6 inherited. What changes is the wire format:
 * the Vue client sends `X-Inertia` and gets an Inertia page, the legacy jQuery
 * client doesn't and gets the flat HTML payload.
 */
it('renders a full page when nothing marks the request as a slideout', function () {
    get(editUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('settings/entry-types/Edit')
            ->where('screen.mode', 'page'));
});

it('loads screen assets with the initial document', function () {
    get(editUrl())
        ->assertOk()
        ->assertSee('/legacy/jquery/dist/jquery.js', false)
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->missing('headHtml')
            ->missing('bodyHtml'));
});

/**
 * `assertInertia()` isn't usable here: it reads the `page` view data, which
 * only exists on the blade-rendered response. A slideout is always an XHR
 * request, so the page object arrives as the JSON body instead.
 */
function slideoutResponse(): TestResponse
{
    return withHeaders([
        'X-Inertia' => 'true',
        'X-Craft-Container-Id' => 'slideout-1',
    ])->getJson(editUrl());
}

it('renders an Inertia page for a Vue slideout request', function () {
    $response = slideoutResponse()->assertOk()->assertHeader('X-Inertia', 'true');

    expect($response->json())
        ->toHaveKeys(['component', 'props', 'url', 'version'])
        // Same component as the full page — that's the whole point.
        ->and($response->json('component'))->toBe('settings/entry-types/Edit')
        ->and($response->json('props.screen.mode'))->toBe('slideout')
        ->and($response->json('props.screen.containerId'))->toBe('slideout-1')
        ->and($response->json('props.screen.namespace'))->toBeString()
        // The blade root view never renders for an XHR response, so the
        // screen's assets have to travel as props.
        ->and($response->json('props'))->toHaveKeys(['headHtml', 'bodyHtml']);
});

it('omits full-page chrome from a slideout', function () {
    expect(slideoutResponse()->assertOk()->json('props'))
        ->not->toHaveKey('crumbs')
        ->not->toHaveKey('subnav')
        ->not->toHaveKey('sidebar')
        ->not->toHaveKey('contextMenu');
});

/**
 * The legacy `Craft.CpScreenSlideout` reads these keys directly. Every one of
 * them is load-bearing for the jQuery slideout stack, so this pins the payload
 * shape rather than just spot-checking it.
 */
it('still returns the legacy flat payload when the client is not Inertia', function () {
    $response = withHeaders(['X-Craft-Container-Id' => 'slideout-1'])
        ->getJson(editUrl())
        ->assertOk();

    expect(array_keys($response->json()))->toBe([
        'editUrl',
        'namespace',
        'title',
        'notice',
        'tabs',
        'bodyClass',
        'formAttributes',
        'action',
        'extraToolbarItems',
        'submitButtonLabel',
        'actionMenu',
        'content',
        'inertiaPage',
        'inertiaProps',
        'sidebar',
        'errorSummary',
        'headHtml',
        'bodyHtml',
        'deltaNames',
        'initialDeltaValues',
    ]);
});

it('gives each slideout its own input namespace', function () {
    $namespaceFor = fn (string $containerId) => withHeaders([
        'X-Craft-Container-Id' => $containerId,
    ])->getJson(editUrl())->json('namespace');

    expect($namespaceFor('slideout-1'))->not->toBe($namespaceFor('slideout-2'));
});
