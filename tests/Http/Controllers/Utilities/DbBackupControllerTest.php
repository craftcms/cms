<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\DbBackup;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access database backup utility', function () {
    Cms::config()->disabledUtilities = [DbBackup::id()];

    postJson(action(DbBackupController::class))
        ->assertForbidden();
});

test('authorized users can create database backup without download', function () {
    $response = postJson(action(DbBackupController::class), [
        'downloadBackup' => false,
    ]);

    expect($response->getStatusCode())->not()->toBe(403);
});

test('authorized users can download database backup', function () {
    $response = postJson(action(DbBackupController::class), [
        'downloadBackup' => true,
    ]);

    expect($response->getStatusCode())->not()->toBe(403);
});
