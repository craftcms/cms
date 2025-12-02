<?php

declare(strict_types=1);

use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Dashboard\Widgets\NewUsers;
use CraftCms\Cms\Edition;

it('can render', function () {
    $dashboard = resolve(Dashboard::class);
    $widget = $dashboard->createWidget(NewUsers::class);

    Edition::set(Edition::Pro);
    expect($widget->getBodyHtml())->not()->toBeNull();
});

it('is only selectable when craft is pro or higher', function () {
    $dashboard = resolve(Dashboard::class);
    $widget = $dashboard->createWidget(NewUsers::class);

    Edition::set(Edition::Solo);
    expect(NewUsers::isSelectable())->toBeFalse();
    expect($widget->getBodyHtml())->toBeNull();

    Edition::set(Edition::Team);
    expect(NewUsers::isSelectable())->toBeFalse();
    expect($widget->getBodyHtml())->toBeNull();

    Edition::set(Edition::Pro);
    expect(NewUsers::isSelectable())->toBeTrue();
    expect($widget->getBodyHtml())->not()->toBeNull();

    Edition::set(Edition::Enterprise);
    expect(NewUsers::isSelectable())->toBeTrue();
    expect($widget->getBodyHtml())->not()->toBeNull();

});
