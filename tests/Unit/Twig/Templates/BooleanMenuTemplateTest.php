<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

function renderBooleanMenu(string $value): string
{
    return renderString(
        "{% include '_includes/forms/booleanMenu' with {id: 'bm', name: 'enabled', value: $value} only %}",
        templateMode: TemplateMode::Cp,
    );
}

it('selects the Yes option for a true value', function () {
    // The template only normalizes falsy values, so `true` arrives at the
    // combobox as a bool, which its `value()` signature rejects.
    expect(renderBooleanMenu('true'))
        ->toContainTag('craft-combobox', ['model-value' => '1']);
});

it('selects the No option for a false value', function () {
    expect(renderBooleanMenu('false'))
        ->toContainTag('craft-combobox', ['model-value' => '0']);
});

it('selects the Yes option for an integer value', function () {
    expect(renderBooleanMenu('1'))
        ->toContainTag('craft-combobox', ['model-value' => '1']);
});

it('keeps a string value as it is', function () {
    expect(renderBooleanMenu("'1'"))
        ->toContainTag('craft-combobox', ['model-value' => '1'])
        ->and(renderBooleanMenu("'0'"))
        ->toContainTag('craft-combobox', ['model-value' => '0']);
});
