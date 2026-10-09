<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementActivity as ElementActivityService;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Http\Controllers\Elements\ElementActivityController;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication', function () {
    Auth::logout();

    postJson(action(ElementActivityController::class), [
        'elementType' => Entry::class,
    ])->assertUnauthorized();
});

it('returns responses resolved by the element request', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);

    post(action(ElementActivityController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'draftId' => 999999,
        'siteId' => $entry->siteId,
    ])->assertRedirect($entry->getCpEditUrl());
});

it('returns 400 when no element is identified by the request', function () {
    postJson(action(ElementActivityController::class), [
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

    postJson(action(ElementActivityController::class), [
        'elementType' => Entry::class,
        'revisionId' => $revision->revisionId,
        'siteId' => $revision->siteId,
    ])->assertBadRequest();
});

it('returns recent activity and tracks the current user viewing the element', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Canonical Title',
        'slug' => 'canonical-title',
    ]);
    $otherUser = UserModel::factory()->createElement();

    app(ElementActivityService::class)->trackActivity($entry, ElementActivityType::Edit, $otherUser);

    $response = postJson(action(ElementActivityController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json
            ->where('updatedTimestamp', $entry->dateUpdated->getTimestamp())
            ->where('canonicalUpdatedTimestamp', $entry->getCanonical()->dateUpdated->getTimestamp())
            ->has('activity', 1)
            ->where('activity.0.userId', $otherUser->id)
            ->where('activity.0.userName', $otherUser->getName())
            ->where('activity.0.type', ElementActivityType::Edit->value)
        );

    expect($response->json('activity.0.message'))->toContain('is editing this entry.');

    $viewerActivity = DB::table(Table::ELEMENTACTIVITY)
        ->where('elementId', $entry->id)
        ->where('userId', auth()->id())
        ->where('siteId', $entry->siteId)
        ->whereNull('draftId')
        ->where('type', ElementActivityType::View->value)
        ->first();

    expect($viewerActivity)->not->toBeNull();
});
