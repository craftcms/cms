<?php

use CraftCms\Cms\Edition;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;

it('can render', function () {
    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(NewUsers::class);

    Craft::$app->setEdition(Edition::Pro);
    expect($widget->getBodyHtml())->not()->toBeNull();
});

it('is only selectable when craft is pro or higher', function () {
    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(NewUsers::class);

    Craft::$app->setEdition(Edition::Solo);
    expect(NewUsers::isSelectable())->toBeFalse();
    expect($widget->getBodyHtml())->toBeNull();

    Craft::$app->setEdition(Edition::Team);
    expect(NewUsers::isSelectable())->toBeFalse();
    expect($widget->getBodyHtml())->toBeNull();

    Craft::$app->setEdition(Edition::Pro);
    expect(NewUsers::isSelectable())->toBeTrue();
    expect($widget->getBodyHtml())->not()->toBeNull();

    Craft::$app->setEdition(Edition::Enterprise);
    expect(NewUsers::isSelectable())->toBeTrue();
    expect($widget->getBodyHtml())->not()->toBeNull();

});
