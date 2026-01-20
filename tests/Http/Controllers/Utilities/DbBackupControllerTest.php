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
