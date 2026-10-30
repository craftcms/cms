<?php

use CraftCms\Cms\View\TemplateManager;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;

function renderCpString(string $template, array $variables = []): string
{
    return app(TemplateManager::class)->renderString($template, $variables, TemplateMode::Cp);
}

it('serves the legacy form includes from the adapter', function(string $template) {
    $path = app(TemplateResolver::class)->resolve("_includes/forms/$template", TemplateMode::Cp);

    expect($path)->toStartWith(realpath(__DIR__ . '/../../resources/templates/_includes/forms'));
})->with([
    'buttonGroup',
    'cardViewDesigner',
    'componentSelect/legacy',
    'copytext',
    'datetime',
    'fieldLayoutDesigner',
    'file',
    'generatedFieldsTable',
    'hidden',
    'password',
    'radio',
]);

it('renders the legacy markup for an explicit componentSelect jsClass', function() {
    $html = renderCpString(
        "{% include '_includes/forms/componentSelect' with {id: 'types', name: 'types[]', options: [], jsClass: 'Craft.ComponentSelectInput'} only %}",
    );

    expect($html)
        ->toContain('class="componentselect"')
        ->not->toContain('<craft-component-select');
});

it('renders plain hidden and file inputs', function() {
    expect(renderCpString("{% include '_includes/forms/hidden' with {name: 'foo', value: 'bar'} only %}"))
        ->toContain('type="hidden"')
        ->toContain('name="foo"')
        ->toContain('value="bar"');

    expect(renderCpString("{% include '_includes/forms/file' with {name: 'upload'} only %}"))
        ->toContain('type="file"')
        ->toContain('name="upload"');
});

it('renders the moved delegates through their components', function(string $include, string $tag) {
    expect(renderCpString($include))->toContain("<$tag");
})->with([
    'copytext' => ["{% include '_includes/forms/copytext' with {id: 'c', value: 'x'} only %}", 'craft-input-copy'],
    'password' => ["{% include '_includes/forms/password' with {id: 'p', name: 'pw'} only %}", 'craft-input-password'],
    'radio' => ["{% include '_includes/forms/radio' with {id: 'r', name: 'mode', value: 'a'} only %}", 'craft-radio'],
    'buttonGroup' => ["{% include '_includes/forms/buttonGroup' with {id: 'bg', name: 'a', options: [{label: 'L', value: 'l'}]} only %}", 'craft-button-group'],
    'datetime' => ["{% include '_includes/forms/datetime' with {id: 'd', name: 'when'} only %}", 'craft-input-date-time'],
]);
