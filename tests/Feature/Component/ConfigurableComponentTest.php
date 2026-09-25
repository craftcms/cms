<?php

declare(strict_types=1);

use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Support\Facades\Fields;

class ConfigurableComponentTestPlainText extends PlainText
{
    public bool $extraSetting = false;
}

it('includes settings declared by concrete parent classes', function () {
    $attributes = new ConfigurableComponentTestPlainText()->settingsAttributes();

    expect($attributes)
        ->toContain('extraSetting', 'placeholder', 'charLimit', 'multiline')
        ->not->toContain('name', 'handle', 'instructions', 'searchable');
});

it('preserves settings declared by concrete parent classes when saving a field', function () {
    $field = Fields::createField([
        'type' => ConfigurableComponentTestPlainText::class,
        'name' => 'Subclassed Text',
        'handle' => 'subclassedText',
        'settings' => [
            'charLimit' => 50,
            'multiline' => true,
            'extraSetting' => true,
        ],
    ]);

    expect(Fields::saveField($field))->toBeTrue();

    $savedField = Fields::getFieldById($field->id);

    expect($savedField)->toBeInstanceOf(ConfigurableComponentTestPlainText::class)
        ->and($savedField->charLimit)->toBe(50)
        ->and($savedField->multiline)->toBeTrue()
        ->and($savedField->extraSetting)->toBeTrue();
});
