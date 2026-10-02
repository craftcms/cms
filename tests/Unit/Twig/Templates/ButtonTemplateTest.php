<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders the web component from the legacy button variables', function () {
    $html = renderString(
        "{% include '_includes/forms/button' with {type: 'submit', label: 'Save', spinner: true, busyMessage: 'Saving…'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)
        ->toContainTag('craft-button', [
            'type' => 'submit',
            'data-busy-message' => 'Saving…',
        ])
        ->and($html)->toContain('Save');
});

it('maps readOnly to a disabled button with the read-only class', function () {
    $html = renderString(
        "{% include '_includes/forms/button' with {label: 'Locked', readOnly: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)
        ->toContainTag('craft-button', ['disabled' => true])
        ->and($html)->toContain('read-only');
});

it('renders the web component from the legacy buttonGroup variables', function () {
    $html = renderString(
        "{% include '_includes/forms/buttonGroup' with {id: 'bg', name: 'align', value: 'left', options: [{label: 'Left', value: 'left'}, {label: 'Right', value: 'right'}]} only %}",
        templateMode: TemplateMode::Cp,
    );

    // The group is form-associated: `name`/`value` ride on the host and it
    // submits the selection itself, so there's no hidden input.
    expect($html)
        ->toContainTag('craft-button-group', [
            'id' => 'bg',
            'name' => 'align',
            'value' => 'left',
            'role' => 'radiogroup',
        ])
        ->toContainTag('craft-button', [
            'data-value' => 'left',
            'type' => 'button',
            'variant' => 'outline',
            'active' => 'true',
            'aria-pressed' => 'true',
        ])
        ->toContainTag('craft-button', [
            'data-value' => 'right',
            'type' => 'button',
            'variant' => 'outline',
            'aria-pressed' => 'false',
        ])
        ->and($html)->not->toContainTag('input', ['type' => 'hidden']);
});
