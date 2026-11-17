<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Http\Controllers\PreviewController;
use CraftCms\Cms\Http\Routing\ActionRoute;
use CraftCms\Cms\RouteToken\Model\RouteToken;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::find()->one());

    $this->entry = Entry::factory()->create();

    SessionAuth::authorize("previewElement:{$this->entry->id}");
});

it('can create a token', function () {
    expect(RouteToken::count())->toBe(0);

    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
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
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'canonicalId' => $this->entry->id,
    ])->assertJsonValidationErrorFor('siteId');
});

test('canonicalId is required without sourceId', function () {
    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
    ])->assertJsonValidationErrorFor('sourceId');

    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'sourceId' => $this->entry->id,
    ])->assertOk();
});

it('redirects when a redirect is passed', function () {
    postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'redirect' => 'https://example.com',
    ])->assertRedirect('https://example.com');
});

test('preview token targets are not directly routable', function () {
    get(ActionRoute::uriForSegments(['preview', 'preview'], false))
        ->assertNotFound();
});

test('it can preview elements', function () {
    $token = postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
    ])->json('token');

    $entryId = $this->entry->id;
    Route::get('/', function () use ($entryId) {
        $entry = Elements::getElementById($entryId, CraftCms\Cms\Entry\Elements\Entry::class);

        return $entry?->previewing ? 'previewing' : 'not previewing';
    });

    get('/?token='.$token)
        ->assertOk()
        ->assertSee('previewing')
        ->assertDontSee('not previewing');
});

it('accepts token params from the query string', function () {
    get(action([PreviewController::class, 'createToken'], [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'redirect' => 'https://example.com',
    ]))->assertRedirect('https://example.com');
});

it('ignores params that are not part of the token', function () {
    get(action([PreviewController::class, 'createToken'], [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'redirect' => 'https://example.com',
        // Control panel URLs carry this; it isn't a token property.
        'site' => 'default',
    ]))->assertRedirect('https://example.com');
});

it('reuses a pre-generated token that already exists', function () {
    $params = [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'previewToken' => Crypt::encrypt($token = Str::random(32)),
    ];

    postJson(action([PreviewController::class, 'createToken']), $params)
        ->assertOk()
        ->assertJson(['token' => $token]);

    get(action([PreviewController::class, 'createToken'], [...$params, 'redirect' => 'https://example.com']))
        ->assertRedirect('https://example.com');

    expect(RouteToken::count())->toBe(1);
});

it('keeps a reused token alive, and recreates it once it has expired', function () {
    $params = [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'previewToken' => Crypt::encrypt($token = Str::random(32)),
    ];

    postJson(action([PreviewController::class, 'createToken']), $params)->assertOk();
    RouteToken::where('token', $token)->update(['expiryDate' => now()->addMinute()]);

    postJson(action([PreviewController::class, 'createToken']), $params)->assertOk();
    expect(RouteToken::where('token', $token)->value('expiryDate'))
        ->toBeGreaterThan(now()->addHour());

    // Expired tokens are deleted once anything resolves a token.
    RouteToken::where('token', $token)->delete();

    postJson(action([PreviewController::class, 'createToken']), $params)
        ->assertOk()
        ->assertJson(['token' => $token]);
    expect(RouteToken::where('token', $token)->count())->toBe(1);
});

it('rejects a pre-generated token that routes somewhere else', function () {
    $other = Entry::factory()->create();
    SessionAuth::authorize("previewElement:{$other->id}");

    $params = [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
        'previewToken' => Crypt::encrypt(Str::random(32)),
    ];

    postJson(action([PreviewController::class, 'createToken']), $params)->assertOk();
    postJson(action([PreviewController::class, 'createToken']), [...$params, 'canonicalId' => $other->id])
        ->assertBadRequest();
});

it('keeps the preview param on the previewed request', function () {
    $token = postJson(action([PreviewController::class, 'createToken']), [
        'elementType' => CraftCms\Cms\Entry\Elements\Entry::class,
        'siteId' => Site::firstOrFail()->id,
        'canonicalId' => $this->entry->id,
    ])->json('token');

    Route::get('/', fn () => request()->isPreview() ? 'is preview' : 'not preview');

    get('/?token='.$token.'&x-craft-live-preview='.urlencode(Crypt::encrypt('x')))
        ->assertOk()
        ->assertSee('is preview');
});
