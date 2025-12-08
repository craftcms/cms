<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\Feed;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('can render', function () {
    actingAs(User::find()->firstOrFail());
    Session::start();

    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(Feed::class);

    expect($widget->getBodyHtml())->not()->toBeNull();
});
