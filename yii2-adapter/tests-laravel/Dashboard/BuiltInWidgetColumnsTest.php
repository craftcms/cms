<?php

declare(strict_types=1);

use craft\widgets\Feed;
use CraftCms\Cms\Dashboard\Dashboard;
use CraftCms\Cms\Shared\Enums\Color;
use CraftCms\Cms\User\Models\User;
use CraftCms\Yii2Adapter\Tests\DatabaseTestCase;

uses(DatabaseTestCase::class);

beforeEach(function(): void {
    $this->actingAs(User::first());
});

it('keeps a built-in widget’s new color and colspan once it’s loaded again', function(string $type) {
    $dashboard = app(Dashboard::class);
    $widget = $dashboard->createWidget(['type' => $type, 'settings' => ['url' => 'https://example.com/feed', 'title' => 'News']]);
    $dashboard->saveWidget($widget);

    $dashboard->changeWidgetColor($widget->id, Color::Red);
    $dashboard->changeWidgetColspan($widget->id, 2);
    $loaded = $dashboard->getWidgetById($widget->id);

    expect($loaded->color)->toBe('red')
        ->and($loaded->colspan)->toBe(2)
        ->and($loaded->url)->toBe('https://example.com/feed');
})->with([
    'built-in' => [Feed::class],
    'plugin subclass' => [BuiltInWidgetColumnsPluginFeed::class],
]);

class BuiltInWidgetColumnsPluginFeed extends Feed
{
}
