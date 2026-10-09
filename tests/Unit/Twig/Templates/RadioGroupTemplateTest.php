<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders radios from the legacy radioGroup variables', function () {
    $html = renderString(
        "{% include '_includes/forms/radioGroup' with {id: 'g', name: 'mode', options: {auto: 'Auto', manual: 'Manual'}, value: 'manual'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<craft-radio-group')
        ->and($html)->toContain('class="radio-group"')
        ->and($html)->toContain('id="g"')
        ->and(substr_count($html, '</craft-radio>'))->toBe(2)
        ->and(substr_count($html, ' checked'))->toBe(1) // input of the checked option
        ->and($html)->toContain('value="manual" checked')
        ->and($html)->not->toContain('type="hidden"');
});

it('wires the fieldtoggle class and target prefix when toggling', function () {
    $html = renderString(
        "{% include '_includes/forms/radioGroup' with {id: 'g', options: {a: 'A'}, toggle: true, targetPrefix: 'opt-'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('fieldtoggle')
        ->and($html)->toContain('data-target-prefix="opt-"');
});
