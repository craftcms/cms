<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\Enums\TabType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;

it('defaults tabs to content tabs', function () {
    $tab = FieldLayoutTab::createFromConfig(['name' => 'Content']);

    expect($tab->type)->toBe(TabType::Content)
        ->and($tab->getConfig()['type'])->toBe('content');
});

it('round-trips sidebar tabs through their config', function () {
    $tab = FieldLayoutTab::createFromConfig(['name' => 'Meta', 'type' => 'sidebar']);

    expect($tab->type)->toBe(TabType::Sidebar)
        ->and($tab->getConfig()['type'])->toBe('sidebar');
});

it('places sidebar tabs after content tabs', function () {
    $layout = new FieldLayout;
    $layout->setTabs([
        ['name' => 'Meta', 'type' => 'sidebar'],
        ['name' => 'Content'],
        ['name' => 'SEO', 'type' => 'sidebar'],
        ['name' => 'Details', 'type' => 'content'],
    ]);

    $tabs = $layout->getTabs();

    expect(array_map(fn (FieldLayoutTab $tab) => $tab->name, $tabs))
        ->toBe(['Content', 'Details', 'Meta', 'SEO'])
        ->and(array_map(fn (FieldLayoutTab $tab) => $tab->sortOrder, $tabs))
        ->toBe([1, 2, 3, 4]);
});
