<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders the fieldset from the legacy checkboxSelect variables', function () {
    $html = renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha', b: 'Beta'}, values: ['b']} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<fieldset')
        ->and($html)->toContain('id="s"')
        ->and($html)->toContain('class="cp-checkbox-select"')
        ->and($html)->toContain('<input type="hidden" name="sources" value>')
        ->and($html)->toContain('name="sources[]"')
        ->and($html)->toContain('<div class="cp-checkbox-select__item">')
        ->and(substr_count($html, '<craft-checkbox'))->toBe(2);
});

it('renders a checked All option that disables the items', function () {
    $html = renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha'}, values: '*', showAllOption: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    // The only hidden input is the All checkbox's own always-post input.
    expect($html)->toContain('<b>All</b>')
        ->and($html)->toContain('value="*"')
        ->and(substr_count($html, 'type="hidden"'))->toBe(1)
        ->and(substr_count($html, ' checked'))->toBeGreaterThanOrEqual(2) // All + item
        ->and($html)->toContain(' disabled');
});

it('pre-orders sortable options by the values order and wraps them', function () {
    $html = renderString(
        "{% include '_includes/forms/checkboxSelect' with {id: 's', name: 'sources', options: {a: 'Alpha', b: 'Beta'}, values: ['b', 'a'], sortable: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<craft-sortable-checkbox-select>')
        ->and(strpos($html, 'value="b"'))->toBeLessThan(strpos($html, 'value="a"'));
});
