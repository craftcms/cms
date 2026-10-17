<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\LegacyTabsShim;

it('names the tab list it renders for anchored tabs', function () {
    $html = LegacyTabsShim::apply([
        ['label' => 'API Connection', 'url' => '#api'],
        ['label' => 'Advanced', 'url' => '#advanced'],
    ]);

    expect($html)->toContain('label="Primary fields"')
        ->toContain('controls="api"')
        ->toContain('controls="advanced"');
});

it('shows tabs’ icons before their labels', function () {
    $html = LegacyTabsShim::apply([
        ['label' => 'Content', 'url' => '#content', 'icon' => 'newspaper'],
        ['label' => 'Meta', 'url' => '#meta'],
    ]);

    expect($html)->toContain('<craft-icon slot="prefix" name="newspaper"')
        ->and(substr_count($html, '<craft-icon'))->toBe(1);
});
