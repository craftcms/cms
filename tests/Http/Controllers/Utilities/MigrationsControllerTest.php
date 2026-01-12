<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\Migrations;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    actingAs(User::findOne());
});

test('unauthorized users cannot access migrations utility', function () {
    Cms::config()->disabledUtilities = [Migrations::id()];

    post(action(MigrationsController::class))
        ->assertForbidden();
});

test('successful migration', function () {
    post(action(MigrationsController::class))
        ->assertRedirect(Cms::config()->cpTrigger.'/utilities/migrations');
});

test('migration handles exceptions', function () {
    post(action(MigrationsController::class))
        ->assertRedirect(Cms::config()->cpTrigger.'/utilities/migrations');
});
