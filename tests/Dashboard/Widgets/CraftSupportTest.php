<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\CraftSupport;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Session;

use function Pest\Laravel\actingAs;

it('can render', function () {
    actingAs(User::find()->one());
    Session::start();

    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(CraftSupport::class);

    expect($widget->getBodyHtml())->not()->toBeNull();
});

it('is only selectable by admins', function () {
    UserModel::first()->update(['admin' => false]);

    actingAs(User::find()->one());
    Session::start();

    expect(CraftSupport::isSelectable())->toBeFalse();

    UserModel::first()->update(['admin' => true]);
    actingAs(User::find()->one());

    expect(CraftSupport::isSelectable())->toBeTrue();
});
