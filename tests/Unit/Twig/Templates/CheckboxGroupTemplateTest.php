<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateMode;

use function CraftCms\Cms\renderString;

it('renders checkboxes from the legacy checkboxGroup variables', function () {
    $html = renderString(
        "{% include '_includes/forms/checkboxGroup' with {id: 'g', name: 'colors', options: {red: 'Red', blue: 'Blue'}, values: ['blue']} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('<craft-checkbox-group id="g" class="checkbox-group">')
        ->and($html)->toContain('<input type="hidden" name="colors" value>')
        ->and($html)->toContain('name="colors[]"')
        ->and($html)->toContain('>Red</label>')
        ->and($html)->toContain('>Blue</label>')
        ->and(substr_count($html, '<craft-checkbox>'))->toBe(2)
        ->and(substr_count($html, ' checked'))->toBe(1); // input of the checked option
});

it('renders the add-option button for allowCustomOptions', function () {
    $html = renderString(
        "{% include '_includes/forms/checkboxGroup' with {id: 'g', name: 'opts', options: [], allowCustomOptions: true} only %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('id="g-add-btn"')
        ->and($html)->toContain('Add option');
});

it('merges attr-block attributes onto the container', function () {
    $html = renderString(
        "{% embed '_includes/forms/checkboxGroup' with {id: 'g', options: []} only %}{% block attr %}data-test=\"1\"{% endblock %}{% endembed %}",
        templateMode: TemplateMode::Cp,
    );

    expect($html)->toContain('data-test="1"');
});
