<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders the web component from the legacy checkbox variables', function () {
    $html = renderString(
        "{% include '_includes/forms/checkbox' with {id: 'cb', name: 'agree', label: 'I agree', checked: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContainTag('input', ['type' => 'hidden', 'name' => 'agree', 'value' => ''])
        ->and($html)->toContain('<craft-checkbox>')
        ->and($html)->toContain('type="checkbox"')
        ->and($html)->toContain(' checked')
        ->and($html)->toContainTag('label', ['slot' => 'label', 'for' => 'cb', 'id' => 'cb-label'])
        ->and($html)->toContain('I agree');
});

it('prefers checkboxLabel and suppresses labelledby when aria-label is set', function () {
    $html = renderString(
        "{% include '_includes/forms/checkbox' with {id: 'cb', label: 'Generic', checkboxLabel: 'Specific', labelledBy: 'external', aria: {label: 'Spoken'}} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('>Specific</label>')
        ->and($html)->toContain('aria-label="Spoken"')
        ->and($html)->not->toContain('aria-labelledby');
});

it('wires field toggles from the legacy variables', function () {
    $html = renderString(
        "{% include '_includes/forms/checkbox' with {id: 'cb', targetPrefix: 'opt-'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('fieldtoggle')
        ->and($html)->toContain('data-target-prefix="opt-"');
});
