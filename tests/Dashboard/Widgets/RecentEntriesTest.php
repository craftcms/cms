<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('can render', function () {
    actingAs(User::first());
    Session::start();

    $dashboard = resolve(Dashboard::class);
    $widget = $dashboard->createWidget(RecentEntries::class);

    expect($widget->getBodyHtml())->not()->toBeNull();
});
