<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Form\Controls\PermissionTree;
use CraftCms\Cms\Form\Enums\ControlMode;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
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
    $form = Form::make([
        Field::make('Permissions', PermissionTree::make('permissions')
            ->ariaLabel('Permissions')
            ->groups($groups)
            ->lockedPermissions(['editEntries'])
            ->value(['viewEntries'])),
    ]);
    $payload = app(FormResolver::class)->resolve($form, new FormContext(namespace: 'settings'));
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($payload));
    $control = $payload->nodes[0]->control;
    $permissionTree = $crawler->filter('craft-permission-tree');

    expect($control?->component)->toBe('craft:permission-tree')
        ->and($control?->props['groups'][0]['keys'])->toBe(['viewEntries', 'editEntries'])
        ->and($crawler->filter('[role="group"][aria-label="Permissions"]'))->toHaveCount(1)
        ->and(json_decode((string) $permissionTree->attr('locked-permissions'), true))->toBe(['editEntries'])
        ->and($crawler->filter('input[type="hidden"][name="settings[permissions]"][value=""]'))->toHaveCount(1)
        ->and($crawler->filter('input[type="hidden"][name="settings[permissions][]"][value="viewEntries"]'))->toHaveCount(1);

    $readOnly = app(FormResolver::class)->resolve($form, new FormContext(
        namespace: 'settings',
        mode: ControlMode::ReadOnly,
    ));
    $crawler = new Crawler(app(FormHtmlRenderer::class)->render($readOnly));

    expect($crawler->filter('[name]'))->toHaveCount(0);
});
