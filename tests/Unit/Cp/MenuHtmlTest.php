<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Html\MenuHtml;
use CraftCms\Cms\Element\Enums\MenuItemType;

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
