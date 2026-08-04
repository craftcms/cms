<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\AssetIndexes;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access asset indexes utility', function () {
    Cms::config()->disabledUtilities = [AssetIndexes::id()];

    postJson(action([AssetIndexesController::class, 'startIndexing']), [
        'volumes' => [1],
    ])
        ->assertForbidden();
});

test('start indexing requires volumes parameter', function () {
    postJson(action([AssetIndexesController::class, 'startIndexing']), [])
        ->assertJsonValidationErrors(['volumes']);
});

test('start indexing with no allowed volumes returns error', function () {
    // Pass an invalid volume ID that won't be in the allowed list
    postJson(action([AssetIndexesController::class, 'startIndexing']), [
        'volumes' => [99999],
    ])
        ->assertStatus(400)
        ->assertJson(['message' => 'No volumes specified.']);
});

test('stop indexing session requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'stopIndexingSession']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('stop indexing session returns stop response', function () {
    postJson(action([AssetIndexesController::class, 'stopIndexingSession']), [
        'sessionId' => 999,
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});

test('process indexing session requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'processIndexingSession']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('process indexing session with non-existent session returns stop', function () {
    postJson(action([AssetIndexesController::class, 'processIndexingSession']), [
        'sessionId' => 999,
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});

test('indexing session overview requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'indexingSessionOverview']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('indexing session overview with non-existent session returns error', function () {
    postJson(action([AssetIndexesController::class, 'indexingSessionOverview']), [
        'sessionId' => 999,
    ])
        ->assertStatus(400)
        ->assertJson(['message' => 'Cannot find the indexing session, or there’s nothing to review.']);
});

test('finish indexing session requires sessionId parameter', function () {
    postJson(action([AssetIndexesController::class, 'finishIndexingSession']), [])
        ->assertJsonValidationErrors(['sessionId']);
});

test('finish indexing session returns stop response', function () {
    postJson(action([AssetIndexesController::class, 'finishIndexingSession']), [
        'sessionId' => 999,
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});

test('finish indexing session accepts deleteFolder and deleteAsset parameters', function () {
    postJson(action([AssetIndexesController::class, 'finishIndexingSession']), [
        'sessionId' => 999,
        'deleteFolder' => [1, 2, 3],
        'deleteAsset' => [4, 5, 6],
    ])
        ->assertOk()
        ->assertJson(['stop' => 999]);
});
