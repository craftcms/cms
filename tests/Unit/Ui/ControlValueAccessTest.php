<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldPreviews;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\Ui\Controls\Slug as PluginSlug;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\Ui\Controls\Money;
use CraftCms\Cms\Ui\Controls\NestedElementBlocks;
use CraftCms\Cms\Ui\Controls\Number;
use CraftCms\Cms\Ui\Controls\Table;
use CraftCms\Cms\Ui\Controls\Text;
use CraftCms\Cms\Ui\Nodes\Field;
use CraftCms\Cms\Ui\Ui;
use CraftCms\Cms\Ui\UiContext;
use CraftCms\Cms\Ui\UiControlTypes;
use CraftCms\Cms\Ui\UiResolver;

it('says only a control that opts in has a value scripts can read and write, or one built on a control that does', function () {
    app(UiControlTypes::class)->register(PluginSlug::class);

    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Title', Text::make('title')),
        Field::make('Price', Number::make('price')),
        Field::make('Slug', PluginSlug::make('slug')),
        Field::make('Content', NestedElementBlocks::make('matrix')->entryTypes(['text' => 'Text'])),
    ]), new UiContext(namespace: 'fields'));

    [$text, $number, $plugin, $matrix] = array_map(fn ($node) => $node->control, $payload->nodes);

    expect($text->jsonSerialize())
        ->toHaveKey('readable', true)
        ->toHaveKey('writable', true)
        ->not->toHaveKey('valueProps')
        ->and([$number->readable, $number->writable])->toBe([true, true])
        ->and($plugin->jsonSerialize())->not->toHaveKeys(['readable', 'writable'])
        ->and([$matrix->readable, $matrix->writable])->toBe([false, false]);
});

it('copies values only between controls in the same value scope', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Title', Text::make('title')),
        Field::make('Price', Money::make('price')->currency('EUR')),
        Field::make('Hero', ElementSelect::make('hero')->elementType(Entry::class)->valueScope('hero-field')),
    ]), new UiContext(namespace: 'fields'));

    [$text, $money, $hero] = array_map(fn ($node) => $node->control->jsonSerialize(), $payload->nodes);

    expect($text)->not->toHaveKey('valueScope')
        ->and($money['valueScope'])->toBe('EUR')
        ->and($hero['valueScope'])->toBe('hero-field')
        ->and(ElementSelect::make('hero')->elementType(Entry::class)->getValueScope())->toBe(Entry::class);
});

it('reads a relation field’s element presentations along with its value', function () {
    expect(ElementSelect::make('hero')->valueProps())->toBe(['elements', 'elementType']);
});

it('still says a control with nested UIs can be read and written once it’s been prepared for a comparison', function () {
    $payload = app(UiResolver::class)->resolve(Ui::make([
        Field::make('Rows', Table::make('rows')->columns([
            'name' => ['heading' => 'Name', 'control' => Text::make('name')],
        ])->valueScope('guests')->value([['name' => 'Ada']])),
    ]), new UiContext(namespace: 'fields'));

    $table = app(FieldPreviews::class)->withoutActionMenus($payload->nodes[0])->control;

    expect($table->uis)->not->toBeEmpty()
        ->and([$table->readable, $table->writable])->toBe([true, true])
        ->and($table->valueScope)->toBe('guests');
});

it('tells copied table values which columns hold single-line text, and which have controls of their own', function () {
    $columns = Table::make('rows')->columns([
        'name' => ['heading' => 'Name'],
        'size' => ['heading' => 'Size', 'control' => Number::make('size')],
    ])->props()['columns'];

    expect($columns['name']['type'])->toBe('singleline')
        ->and($columns['size'])->not->toHaveKey('type');
});
