<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Http\Controllers\Utilities\MigrationsController;
use CraftCms\Cms\Support\Flash;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\Utility\Utilities\Migrations;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;
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
        ->assertRedirect(Url::cpUrl('utilities/migrations'))
        ->assertMessage('success', 'Applied new migrations successfully.');
});

test('migration handles exceptions', function () {
    mock(Migrator::class, function (MockInterface $migrator) {
        $migrator->expects('track')->with('content')->andReturnSelf();
        $migrator->expects('run')->andThrow(new RuntimeException('Migration failed'));
    });

    post(action(MigrationsController::class))
        ->assertRedirect(Url::cpUrl('utilities/migrations'))
        ->assertMessage('error', 'Couldn’t apply new migrations.');

    expect(array_column(session(Flash::SESSION_KEY, []), 'type'))->not->toContain('success');
});
