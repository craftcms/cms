<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\RouteToken\Model\RouteToken;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::first());

    $this->entry = Entry::factory()->create();
});

it('can create a token', function () {
    expect(RouteToken::count())->toBe(0);

    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => \craft\elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
    ])->assertOk();

    expect(RouteToken::count())->toBe(1);
});

test('elementType is required', function () {
    postJson(action([PreviewController::class, 'createToken']), [
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
    ])->assertJsonValidationErrorFor('elementType');
});

test('siteId is required', function () {
    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => \craft\elements\Entry::class,
        'canonicalId' => $this->entry->id,
    ])->assertJsonValidationErrorFor('siteId');
});

test('canonicalId is required without sourceId', function () {
    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => \craft\elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
    ])->assertJsonValidationErrorFor('sourceId');

    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => \craft\elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'sourceId' => $this->entry->id,
    ])->assertOk();
});

it('redirects when a redirect is passed', function () {
    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => \craft\elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'redirect' => 'https://example.com',
    ])->assertRedirect('https://example.com');
});

test('preview requires a valid token', function () {
    get(route('craft.actions.preview'))
        ->assertUnauthorized();
});

test('it can preview elements', function () {
    $token = postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => \craft\elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
    ])->json('token');

    $entryId = $this->entry->id;
    Route::get('/', function () use ($entryId) {
        $entry = Craft::$app->elements->getElementById($entryId, \craft\elements\Entry::class);

        return $entry?->previewing ? 'previewing' : 'not previewing';
    });

    get('/?token='.$token)
        ->assertOk()
        ->assertSee('previewing')
        ->assertDontSee('not previewing');
});
