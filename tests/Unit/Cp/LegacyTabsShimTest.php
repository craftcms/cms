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

it('selects the tab the screen says it is on', function () {
    $html = LegacyTabsShim::apply([
        'api' => ['label' => 'API Connection', 'url' => '#api'],
        'advanced' => ['label' => 'Advanced', 'url' => '#advanced'],
    ], 'advanced');

    expect($html)->toContain('selected-index="1"');
});

it('falls back to the first tab given a selection it has no tab for', function () {
    $html = LegacyTabsShim::apply([
        'api' => ['label' => 'API Connection', 'url' => '#api'],
        'advanced' => ['label' => 'Advanced', 'url' => '#advanced'],
    ], 'nope');

    expect($html)->toContain('selected-index="0"');
});

it('renders tabs that are links to other pages as a nav of them', function () {
    $html = LegacyTabsShim::apply([
        'feeds' => ['label' => 'Feeds', 'url' => 'https://example.test/admin/feed-me/feeds'],
        'logs' => ['label' => 'Logs', 'url' => 'https://example.test/admin/feed-me/logs'],
    ], 'logs', 'Feed Me');

    expect($html)->toContain('<nav aria-label="Feed Me">')
        ->toContain('<craft-nav-list appearance="tabs">')
        ->toContain('href="https://example.test/admin/feed-me/feeds"')
        ->toContain('href="https://example.test/admin/feed-me/logs"')
        ->not->toContain('craft-tabs')
        ->not->toContain('role="tab"');
});

it('marks only the page the screen is on as current', function () {
    $html = LegacyTabsShim::apply([
        'feeds' => ['label' => 'Feeds', 'url' => '/admin/feed-me/feeds'],
        'logs' => ['label' => 'Logs', 'url' => '/admin/feed-me/logs'],
    ], 'logs');

    expect(substr_count($html, 'current'))->toBe(1)
        ->and($html)->toMatch('/<craft-nav-item[^>]*href="\/admin\/feed-me\/logs"[^>]*current/');
});

it('names a nav after the pages it moves between when the screen is untitled', function () {
    $html = LegacyTabsShim::apply([
        'feeds' => ['label' => 'Feeds', 'url' => '/admin/feed-me/feeds'],
        'logs' => ['label' => 'Logs', 'url' => '/admin/feed-me/logs'],
    ]);

    expect($html)->toContain('<nav aria-label="Pages">');
});

it('keeps the legacy strip for tabs that mix panes and pages', function () {
    $html = LegacyTabsShim::apply([
        ['label' => 'Content', 'url' => '#content'],
        ['label' => 'Logs', 'url' => '/admin/feed-me/logs'],
    ]);

    expect($html)->toBeNull();
});

it('keeps the legacy strip for a tab with no url to read', function () {
    $html = LegacyTabsShim::apply([
        ['label' => 'Content'],
        ['label' => 'Logs', 'url' => '/admin/feed-me/logs'],
    ]);

    expect($html)->toBeNull();
});
