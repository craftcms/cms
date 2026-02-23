<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\RecentEntries;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('can render', function () {
    actingAs(User::find()->one());
    Session::start();

    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(RecentEntries::class);

    expect($widget->getBodyHtml())->not()->toBeNull();
});
