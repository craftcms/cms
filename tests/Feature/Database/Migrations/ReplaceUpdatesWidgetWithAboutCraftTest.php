<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Models\Widget;
use CraftCms\Cms\Dashboard\Widgets\AboutCraft;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\User\Models\User;

const UPDATES_WIDGET = 'CraftCms\\Cms\\Dashboard\\Widgets\\Updates';

/** @param array<string, mixed> $attributes */
function addWidgetForUser(int $userId, string $type, int $sortOrder, array $attributes = []): Widget
{
    $widget = new Widget;
    $widget->userId = $userId;
    $widget->type = $type;
    $widget->sortOrder = $sortOrder;
    $widget->settings = [];
    $widget->forceFill($attributes);
    $widget->save();

    return $widget;
}

function runReplaceUpdatesWidgetMigration(): void
{
    $migration = require dirname(__DIR__, 4).'/src/Database/Migrations/2026_11_17_000001_replace_updates_widget_with_about_craft.php';
    $migration->up();
}

beforeEach(fn () => Widget::query()->delete());

it('replaces Updates widgets with About Craft CMS widgets in their place, for any user', function () {
    $user = User::factory()->create(['hasDashboard' => true]);
    addWidgetForUser($user->id, Feed::class, 1);
    $updates = addWidgetForUser($user->id, UPDATES_WIDGET, 2, ['colspan' => 2, 'color' => 'blue']);
    addWidgetForUser($user->id, Feed::class, 3);

    runReplaceUpdatesWidgetMigration();

    $widget = Widget::query()->findOrFail($updates->id);

    expect($widget->type)->toBe(AboutCraft::class)
        ->and($widget->sortOrder)->toBe(2)
        ->and($widget->colspan)->toBe(2)
        ->and($widget->color)->toBe('blue')
        ->and(Widget::query()->where('userId', $user->id)->count())->toBe(3);
});

it('leaves dashboards without an Updates widget as they are', function () {
    $admin = User::factory()->admin()->create(['hasDashboard' => true]);
    addWidgetForUser($admin->id, Feed::class, 1);
    $user = User::factory()->create(['hasDashboard' => true]);
    addWidgetForUser($user->id, Feed::class, 1);

    runReplaceUpdatesWidgetMigration();

    expect(Widget::query()->where('type', AboutCraft::class)->exists())->toBeFalse()
        ->and(Widget::query()->whereIn('userId', [$admin->id, $user->id])->count())->toBe(2);
});

it('drops an Updates widget beside an existing About Craft CMS widget', function () {
    $admin = User::factory()->admin()->create(['hasDashboard' => true]);
    addWidgetForUser($admin->id, AboutCraft::class, 1);
    addWidgetForUser($admin->id, UPDATES_WIDGET, 2);

    runReplaceUpdatesWidgetMigration();

    expect(Widget::query()->where('userId', $admin->id)->pluck('type')->all())->toBe([AboutCraft::class]);
});
