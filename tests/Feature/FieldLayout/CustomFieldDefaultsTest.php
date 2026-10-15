<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\FieldLayoutElementContext;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Ui\Enums\ControlMode;
use CraftCms\Cms\Ui\UiContext;

function customFieldNodeProps(CustomField $element): array
{
    return $element->uiNode(new FieldLayoutElementContext(null, new UiContext, ControlMode::Editable))->props();
}

function fieldWithDefaults(): PlainText
{
    return new PlainText([
        'id' => 1,
        'handle' => 'body',
        'name' => 'Body',
        'tip' => 'Default tip.',
        'warning' => 'Default warning.',
    ]);
}

it('falls back to the field’s default tip and warning', function () {
    $props = customFieldNodeProps(new CustomField(fieldWithDefaults()));

    expect($props['tip'])->toBe('Default tip.')
        ->and($props['warning'])->toBe('Default warning.');
});

it('lets the layout element override the field’s default tip and warning', function () {
    $element = new CustomField(fieldWithDefaults());
    $element->tip = 'Overridden tip.';
    $element->warning = 'Overridden warning.';

    $props = customFieldNodeProps($element);

    expect($props['tip'])->toBe('Overridden tip.')
        ->and($props['warning'])->toBe('Overridden warning.');
});
