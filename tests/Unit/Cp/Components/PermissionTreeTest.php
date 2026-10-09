<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Components\PermissionTree;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use Symfony\Component\DomCrawler\Crawler;

use function CraftCms\Cms\ui;

it('renders permission groups and selected values through the web component', function () {
    $group = new PermissionGroup('content', 'Content', collect([
        new Permission('viewEntries', 'View entries', nested: collect([
            new Permission('editEntries', 'Edit entries'),
        ])),
    ]));
    $crawler = new Crawler(PermissionTree::make()
        ->groups([$group])
        ->modelValue(['viewEntries', 'outsideGroup'])
        ->lockedPermissions(['editEntries'])
        ->name('settings[permissions]')
        ->toHtml());
    $component = $crawler->filter('craft-permission-tree');
    $groups = json_decode((string) $component->attr('groups'), true);

    expect($groups[0]['keys'])->toBe(['viewEntries', 'editEntries'])
        ->and(json_decode((string) $component->attr('locked-permissions'), true))->toBe(['editEntries'])
        ->and(ui('permission-tree'))->toBeInstanceOf(PermissionTree::class)
        ->and($crawler->filter('input[name="settings[permissions]"][value=""]'))->toHaveCount(1)
        ->and($crawler->filter('input[name="settings[permissions][]"][value="viewEntries"]'))->toHaveCount(1)
        ->and($crawler->filter('input[value="outsideGroup"]'))->toHaveCount(0);
});
