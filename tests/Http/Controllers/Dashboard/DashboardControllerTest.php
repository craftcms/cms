<?php

use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Controllers\Dashboard\DashboardController;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('requires login', function () {
    get(action(DashboardController::class))
        ->assertRedirect(Cms::config()->cpTrigger.'/login');
});

it('can be rendered', function () {
    actingAs(User::first());

    get(action(DashboardController::class))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Widget');
});
