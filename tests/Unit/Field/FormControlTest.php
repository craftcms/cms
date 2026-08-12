<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Addresses;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ButtonGroup;
use CraftCms\Cms\Field\Checkboxes;
use CraftCms\Cms\Field\Color;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Country;
use CraftCms\Cms\Field\Date;
use CraftCms\Cms\Field\Dropdown;
use CraftCms\Cms\Field\Email;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\FieldContext;
use CraftCms\Cms\Field\Icon;
use CraftCms\Cms\Field\Json;
use CraftCms\Cms\Field\Lightswitch;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Markdown;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Money;
use CraftCms\Cms\Field\MultiSelect;
use CraftCms\Cms\Field\Number;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Field\RadioButtons;
use CraftCms\Cms\Field\Range;
use CraftCms\Cms\Field\Table;
use CraftCms\Cms\Field\Time;
use CraftCms\Cms\Field\Users;
use CraftCms\Cms\Form\Contracts\Control;

it('provides a Form Control for every built-in field type', function (string $fieldType) {
    $field = $fieldType === Link::class ? new Link(['types' => ['url']]) : new $fieldType;

    expect($field->formControl(new FieldContext('value')))->toBeInstanceOf(Control::class);
})->with([
    'addresses' => Addresses::class,
    'assets' => Assets::class,
    'button group' => ButtonGroup::class,
    'checkboxes' => Checkboxes::class,
    'color' => Color::class,
    'content block' => ContentBlock::class,
    'country' => Country::class,
    'date' => Date::class,
    'dropdown' => Dropdown::class,
    'email' => Email::class,
    'entries' => Entries::class,
    'icon' => Icon::class,
    'JSON' => Json::class,
    'lightswitch' => Lightswitch::class,
    'link' => Link::class,
    'Markdown' => Markdown::class,
    'Matrix' => Matrix::class,
    'money' => Money::class,
    'multi-select' => MultiSelect::class,
    'number' => Number::class,
    'plain text' => PlainText::class,
    'radio buttons' => RadioButtons::class,
    'range' => Range::class,
    'table' => Table::class,
    'time' => Time::class,
    'users' => Users::class,
]);

it('preserves fixed Table column types', function () {
    $types = ['checkbox', 'color', 'date', 'select', 'email', 'heading', 'lightswitch', 'multiline', 'number', 'singleline', 'time', 'url'];
    $field = new Table(['columns' => array_combine($types, array_map(
        fn (string $type): array => [
            'heading' => ucfirst($type),
            'handle' => $type,
            'type' => $type,
            ...($type === 'select' ? ['options' => [['label' => 'One', 'value' => 'one']]] : []),
        ],
        $types,
    ))]);
    $columns = $field->formControl(new FieldContext('value'))->props()['columns'];

    expect(array_map(fn (array $column): string => $column['type'], $columns))->toBe(array_combine($types, $types))
        ->and($columns['select']['options'])->toBe([['label' => 'One', 'value' => 'one']]);
});
