<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires login', function () {
    get(action(DashboardController::class))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('can be rendered', function () {
    actingAs(User::find()->firstOrFail());

    get(action(DashboardController::class))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Widget');
});
