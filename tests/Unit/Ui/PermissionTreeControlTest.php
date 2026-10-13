<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Ui\Controls\PermissionTree;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiHtmlRenderer;
use CraftCms\Cms\Ui\UiResolver;
use CraftCms\Cms\User\Data\Permission;
use CraftCms\Cms\User\Data\PermissionGroup;
use Symfony\Component\DomCrawler\Crawler;

it('resolves and renders selected, inherited, and nested permissions', function () {
    Edition::set(Edition::Solo);
    Cms::setIsInstalled(true);
    $groups = [new PermissionGroup('content', 'Content', collect([
        new Permission('viewEntries', 'View entries', nested: collect([
            new Permission('editEntries', 'Edit entries'),
        ])),
    ]))];
    $ui = Ui::make([
        Field::make('Permissions', PermissionTree::make('permissions')
            ->ariaLabel('Permissions')
            ->groups($groups)
            ->lockedPermissions(['editEntries'])
            ->value(['viewEntries'])),
    ]);
    $payload = app(UiResolver::class)->resolve($ui, new UiContext(namespace: 'settings'));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($payload));
    $control = $payload->nodes[0]->control;
    $permissionTree = $crawler->filter('craft-permission-tree');

    expect($control?->component)->toBe('craft:permission-tree')
        ->and($control?->props['groups'][0]['keys'])->toBe(['viewEntries', 'editEntries'])
        ->and($crawler->filter('[role="group"][aria-label="Permissions"]'))->toHaveCount(1)
        ->and(json_decode((string) $permissionTree->attr('locked-permissions'), true))->toBe(['editEntries'])
        ->and($crawler->filter('input[type="hidden"][name="settings[permissions]"][value=""]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="hidden"][name="settings[permissions][]"][value="viewEntries"]'))->toHaveCount(1);

    $readOnly = app(UiResolver::class)->resolve($ui, new UiContext(
        namespace: 'settings',
        mode: ControlMode::ReadOnly,
    ));
    $crawler = new Crawler(app(UiHtmlRenderer::class)->render($readOnly));

    expect($crawler->filter('[name]'))->toHaveCount(0);
});
