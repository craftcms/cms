<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ValidateElementController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action(ValidateElementController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns responses resolved by the element request', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    post(action(ValidateElementController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'draftId' => 999999,
        'siteId' => $entry->siteId,
    ])->assertRedirect($entry->getCpEditUrl());
});

it('returns 400 when no element is identified by the request', function () {
    postJson(action(ValidateElementController::class), [
        'elementType' => Entry::class,
        'siteId' => 1,
    ])->assertBadRequest();
});

it('returns 400 for revisions', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);
    /** @var Entry $revision */
    $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

    postJson(action(ValidateElementController::class), [
        'elementType' => Entry::class,
        'revisionId' => $revision->revisionId,
        'siteId' => $revision->siteId,
    ])->assertBadRequest();
});

it('returns a failure response for invalid elements', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
    ]);

    $response = postJson(action(ValidateElementController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])
        ->assertBadRequest()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', t('{type} validation failed.', ['type' => Entry::displayName()]))
            ->where('modelName', 'element')
            ->where('element.title', 'Valid Title')
            ->where('errors.authorIds.0', 'The author ids field is required.')
            ->etc()
        );

    expect($response->json('errorSummary'))->toContain('field-error-key');
});

it('returns a success response for valid elements', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Valid Title',
        'slug' => 'valid-title',
    ]);
    $entry->setAuthorIds([auth()->id()]);
    Elements::saveElement($entry);

    postJson(action(ValidateElementController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('message', t('{type} validation successful.', ['type' => Entry::displayName()]))
            ->where('modelName', 'element')
            ->where('element.title', 'Valid Title')
            ->missing('errors')
            ->etc()
        );
});
