<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\Updates;
use CraftCms\Cms\Support\Api;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('can render', function () {
    Http::fake([
        Api::craftApiEndpoint().'/updates' => [
            'cms' => [],
            'plugins' => [],
        ],
    ]);

    actingAs(User::first());
    Session::start();

    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(Updates::class);

    expect($widget->getBodyHtml())->not()->toBeNull();
});
