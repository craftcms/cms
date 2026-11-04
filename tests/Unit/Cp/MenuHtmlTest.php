<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Element\Enums\MenuItemType;
use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\template;

it('returns empty html for empty menu when omitIfEmpty is true', function () {
    $html = app(MenuHtml::class)->disclosureMenu([], ['omitIfEmpty' => true]);

    expect($html)->toBe('');
});

it('normalizes item types and nested groups', function () {
    $items = app(MenuHtml::class)->normalizeMenuItems([
        ['url' => '/test', 'label' => 'Link'],
        ['hr' => true],
        ['heading' => 'Group', 'items' => [['label' => 'Child']]],
    ]);

    expect($items[0]['type'])->toBe(MenuItemType::Link->value)
        ->and($items[1]['type'])->toBe(MenuItemType::HR->value)
        ->and($items[2]['type'])->toBe(MenuItemType::Group->value)
        ->and($items[2]['items'][0]['type'])->toBe(MenuItemType::Button->value);
});

it('renders a craft-action-menu with grouped headings and the selected item marked current', function () {
    $html = app(MenuHtml::class)->disclosureMenu([
        ['label' => 'Primary', 'url' => '/primary', 'selected' => true],
        ['heading' => 'More sites', 'items' => [
            ['label' => 'Secondary', 'url' => '/secondary', 'status' => 'enabled'],
        ]],
        ['label' => 'Delete', 'action' => 'entries/delete', 'destructive' => true],
    ], [
        'id' => 'context-menu',
        'autoLabel' => true,
        'hiddenLabel' => 'Select context',
        'withSearchInput' => true,
    ]);

    expect($html)->toContainTag('craft-action-menu', ['id' => 'context-menu', 'searchable' => true])
        ->and($html)->toContainTag('craft-button', ['slot' => 'invoker', 'aria-label' => false])
        ->and($html)->toContain('<span class="sr-only">Select context</span> Primary')
        ->and($html)->toContainTag('craft-action-item', ['current' => true])
        ->and($html)->toContain('<div class="action-menu__heading" role="presentation"')
        ->and($html)->toContain('More sites')
        ->and($html)->toContainTag('craft-action-item', ['variant' => 'danger'])
        ->and($html)->not->toContain('data-disclosure-trigger')
        ->and($html)->not->toContain('menu--disclosure');
});

it('uses the given invoker when the menu has no button of its own', function () {
    $html = app(MenuHtml::class)->disclosureMenu([['label' => 'Child']], [
        'withButton' => false,
        'invoker' => '<button type="button" class="btn menubtn">Crumb</button>',
    ]);

    expect($html)->toContainTag('button', ['slot' => 'invoker', 'class' => 'btn menubtn']);
});

it('renders the menu when _includes/disclosuremenu is included directly', function () {
    $html = template('_includes/disclosuremenu', [
        'items' => [['label' => 'Included item', 'url' => '/included']],
        'id' => 'included-menu',
        'hiddenLabel' => 'Included actions',
    ], templateMode: TemplateMode::Cp);

    expect($html)->toContainTag('craft-action-menu', ['id' => 'included-menu'])
        ->and($html)->toContainTag('craft-button', ['slot' => 'invoker', 'aria-label' => 'Included actions'])
        ->and($html)->toContain('Included item');
});

it('names an icon-only menu button when no label is given', function () {
    $html = app(MenuHtml::class)->disclosureMenu([['label' => 'Child']]);

    expect($html)->toContainTag('craft-button', ['slot' => 'invoker', 'aria-label' => 'Actions']);
});

it('renders item info as text rather than a control inside the item', function () {
    $html = app(MenuHtml::class)->disclosureMenu([
        ['label' => 'Child', 'info' => 'Read the **docs** <script>alert(1)</script>'],
    ]);

    expect($html)->not->toContain('craft-info-icon')
        ->and($html)->not->toContain('<script>')
        ->and($html)->toContain('Read the docs');
});
