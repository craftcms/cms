<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders the web component from the legacy button variables', function () {
    $html = renderString(
        "{% include '_includes/forms/button' with {type: 'submit', label: 'Save', spinner: true, busyMessage: 'Saving…'} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<craft-button')
        ->and($html)->toContain('type="submit"')
        ->and($html)->toContain('Save')
        ->and($html)->toContain('data-busy-message="Saving…"');
});

it('maps readOnly to a disabled button with the read-only class', function () {
    $html = renderString(
        "{% include '_includes/forms/button' with {label: 'Locked', readOnly: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain(' disabled')
        ->and($html)->toContain('read-only');
});

it('renders the web component from the legacy buttonGroup variables', function () {
    $html = renderString(
        "{% include '_includes/forms/buttonGroup' with {id: 'bg', name: 'align', value: 'left', options: [{label: 'Left', value: 'left'}, {label: 'Right', value: 'right'}]} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<craft-listbox>')
        ->and($html)->toContain('<craft-button-group id="bg" role="group">')
        ->and($html)->toContain('appearance="outline"')
        ->and($html)->toContain('active="true"')
        ->and($html)->toContain('aria-pressed="true"')
        ->and($html)->toContain('aria-pressed="false"')
        ->and($html)->toContain('type="hidden" name="align" value="left"');
});
