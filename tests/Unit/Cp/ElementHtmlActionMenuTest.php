<?php

declare(strict_types=1);

use CraftCms\Cms\Component\Contracts\Actionable;
use CraftCms\Cms\Component\Contracts\Chippable;
use CraftCms\Cms\Cp\Html\ElementHtml;

/**
 * Minimal `Chippable`+`Actionable` test double, standing in for a real
 * element/component so `chipHtml()`'s action-menu rendering can be exercised
 * without touching the database.
 */
class ActionMenuTestComponent implements Actionable, Chippable
{
    public function __construct(
        private readonly array $items = [],
    ) {}

    public static function get(string|int $id): ?static
    {
        return null;
    }

    public function getId(): string|int|null
    {
        return 1;
    }

    public function getUiLabel(): string
    {
        return 'Test Component';
    }

    public function getActionMenuItems(): array
    {
        return $this->items;
    }
}

function renderChipWithActionItems(array $items): string
{
    $component = new ActionMenuTestComponent($items);

    return app(ElementHtml::class)->chipHtml($component, [
        'showActionMenu' => true,
    ]);
}

it('renders a craft-action-menu instead of the legacy disclosure markup', function () {
    $html = renderChipWithActionItems([]);

    expect($html)->toContain('<craft-action-menu icon="ellipsis">')
        ->and($html)->toContain('slot="invoker"')
        ->and($html)->toContain('slot="content"')
        ->and($html)->toContain('craft-icon')
        ->and($html)->not->toContain('menu--disclosure')
        ->and($html)->not->toContain('disclosureMenu')
        ->and($html)->not->toContain('formsubmit');
});

it('always renders the menu, even with zero items, so JS can inject into it later', function () {
    $html = renderChipWithActionItems([]);

    expect($html)->toContain('<craft-action-menu icon="ellipsis">')
        ->and($html)->toContain('<div slot="content"></div>');
});

it('maps a basic item to a craft-action-item with its icon', function () {
    $html = renderChipWithActionItems([
        ['id' => 'action-foo', 'label' => 'Foo', 'icon' => 'star'],
    ]);

    expect($html)->toContain('<craft-action-item')
        ->and($html)->toContain('icon="star"')
        ->and($html)->toContain('>Foo<');
});

it('tags edit items with data-edit-action and copy items with data-copy-action', function () {
    $html = renderChipWithActionItems([
        ['id' => 'action-edit-1', 'label' => 'Edit'],
        ['id' => 'action-copy-1', 'label' => 'Copy'],
    ]);

    expect($html)->toContain('data-edit-action')
        ->and($html)->toContain('data-copy-action');
});

it('marks destructive items with variant="danger"', function () {
    // Destructive items are excluded from chips/cards by default (per
    // `Actionable::getActionMenuItems()`'s doc comment) — `showInChips` opts
    // one back in.
    $html = renderChipWithActionItems([
        ['label' => 'Delete', 'destructive' => true, 'showInChips' => true],
    ]);

    expect($html)->toContain('variant="danger"');
});

it('renders link items with an href and no action', function () {
    $html = renderChipWithActionItems([
        ['label' => 'View', 'url' => 'https://example.com'],
    ]);

    expect($html)->toContain('href="https://example.com"');
});

it('renders hidden items with the hidden attribute', function () {
    $html = renderChipWithActionItems([
        ['label' => 'Hidden thing', 'hidden' => true],
    ]);

    expect($html)->toMatch('/<craft-action-item[^>]*\bhidden\b/');
});

it('maps action/params/confirm to a craft-action-item action object instead of formsubmit', function () {
    $html = renderChipWithActionItems([
        [
            'label' => 'Delete',
            'destructive' => true,
            'showInChips' => true,
            'action' => 'elements/delete',
            'params' => ['elementId' => 5],
            'confirm' => 'Are you sure?',
        ],
    ]);

    $decoded = html_entity_decode($html);

    expect($decoded)->toContain('"type":"http"')
        ->and($decoded)->toContain('"method":"POST"')
        ->and($decoded)->toContain('elements/delete')
        ->and($decoded)->toContain('"elementId":5')
        ->and($decoded)->toContain('"confirm":"Are you sure?"')
        ->and($html)->not->toContain('data-action=')
        ->and($html)->not->toContain('formsubmit');
});

it('renders a description or handle as a secondary line', function () {
    $html = renderChipWithActionItems([
        ['label' => 'With description', 'description' => 'A description'],
        ['label' => 'With handle', 'handle' => 'aHandle'],
    ]);

    expect($html)->toContain('menu-item-description')
        ->and($html)->toContain('A description')
        ->and($html)->toContain('aHandle');
});

it('filters out non-showInChips items and preserves attributes', function () {
    $html = renderChipWithActionItems([
        ['label' => 'Hidden from chips', 'showInChips' => false],
        [
            'label' => 'Custom attrs',
            'attributes' => ['data' => ['custom' => 'yes']],
        ],
    ]);

    expect($html)->not->toContain('Hidden from chips')
        ->and($html)->toContain('data-custom="yes"');
});
