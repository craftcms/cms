<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders the web component from the legacy radio variables', function () {
    $html = renderString(
        "{% include '_includes/forms/radio' with {id: 'r', name: 'mode', value: 'auto', label: 'Auto', checked: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<craft-radio>')
        ->and($html)->toContain('type="radio"')
        ->and($html)->toContain('value="auto"')
        ->and($html)->toContain('class="radio"')
        ->and($html)->toContainTag('label', ['slot' => 'label', 'for' => 'r', 'id' => 'r-label'])
        ->and($html)->toContain('Auto')
        ->and($html)->not->toContain('type="hidden"');
});

it('renders custom-option mode with the Other label and wrapped text input', function () {
    $html = renderString(
        "{% include '_includes/forms/radio' with {id: 'r', name: 'mode', custom: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('Other:')
        ->and($html)->toContain('custom-option-wrapper')
        ->and($html)->toContain('custom-option-input')
        ->and($html)->toContain('id="r-text"');
});
