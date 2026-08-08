<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Dashboard\Models\Widget;
use CraftCms\Cms\Dashboard\Widgets\QuickPost;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires login', function () {
    get(action([DashboardController::class, 'index']))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('can be rendered', function () {
    actingAs(User::find()->one());

    get(action([DashboardController::class, 'index']))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Widget');
});

it('can render a Quick Post widget with empty settings', function () {
    $user = User::find()->one();
    actingAs($user);
    Widget::query()->create([
        'userId' => $user->id,
        'type' => QuickPost::class,
        'settings' => [],
        'sortOrder' => 1,
    ]);

    get(action([DashboardController::class, 'index']))
        ->assertOk();
});
