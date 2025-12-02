<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\User\Models\User;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('can render', function () {
    actingAs(User::first());
    Session::start();

    $dashboard = resolve(Dashboard::class);
    $widget = $dashboard->createWidget(CraftSupport::class);

    expect($widget->getBodyHtml())->not()->toBeNull();
});

it('is only selectable by admins', function () {
    User::first()->update(['admin' => false]);

    actingAs(User::first());
    Session::start();

    expect(CraftSupport::isSelectable())->toBeFalse();

    User::first()->update(['admin' => true]);
    actingAs(User::first());

    expect(CraftSupport::isSelectable())->toBeTrue();
});
