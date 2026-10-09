<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\DbBackupController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\DbBackup;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access database backup utility', function () {
    Cms::config()->disabledUtilities = [DbBackup::id()];

    postJson(action(DbBackupController::class))
        ->assertForbidden();
});

test('download backup returns a file when requested', function () {
    Cms::config()->backupCommand = 'touch {file}';

    $response = post(
        action(DbBackupController::class),
        ['downloadBackup' => true],
    );

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Type'))->toStartWith('application/zip');
});

test('download backup request over inertia is redirected', function () {
    Cms::config()->backupCommand = 'touch {file}';

    post(
        action(DbBackupController::class),
        ['downloadBackup' => false],
        [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ],
    )->assertRedirect();
});
