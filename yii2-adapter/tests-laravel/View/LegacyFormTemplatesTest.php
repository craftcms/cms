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
    'autosuggest',
    'booleanMenu',
    'button',
    'buttonGroup',
    'cardViewDesigner',
    'checkbox',
    'checkboxGroup',
    'checkboxSelect',
    'color',
    'colorSelect',
    'componentSelect',
    'componentSelect/legacy',
    'copytext',
    'copytextbtn',
    'date',
    'datetime',
    'editableTable',
    'entryTypeSelect',
    'errorList',
    'field',
    'fieldLayoutDesigner',
    'fieldSelect',
    'file',
    'generatedFieldsTable',
    'hidden',
    'iconPicker',
    'languageMenu',
    'lightswitch',
    'money',
    'password',
    'radio',
    'radioGroup',
    'select',
    'selectize',
    'text',
    'textarea',
    'time',
    'timeZone',
    'volume',
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

it('merges the attr block into the moved inputs', function(string $include, string $tag) {
    $html = renderCpString("{% embed '_includes/forms/$include' with {id: 'x', options: []} only %}{% block attr %}data-test=\"1\"{% endblock %}{% endembed %}");

    expect($html)->toMatch("/<$tag [^>]*data-test=\"1\"/");
})->with([
    'text' => ['text', 'input'],
    'checkbox' => ['checkbox', 'input'],
    'checkboxGroup' => ['checkboxGroup', 'craft-checkbox-group'],
    'componentSelect' => ['componentSelect', 'craft-component-select'],
]);

it('renders errorList markup', function() {
    expect(renderCpString("{% include '_includes/forms/errorList' with {errors: ['Bad **value**']} only %}"))
        ->toContain('<ul class="errors">')
        ->toContain('<strong>value</strong>');
});

it('keeps the forms macros working for plugins', function() {
    $html = renderCpString('{% import "_includes/forms" as forms %}{{ forms.checkboxField({id: "cb", name: "enabled", label: "Agree"}) }}{{ forms.textField({id: "t", name: "title", label: "Title"}) }}');

    expect($html)
        ->toContain('>Agree</label>')
        ->toContain('<craft-input')
        ->toContain('name="title"');
});

it('routes an entry type select with a jsClass through the legacy template', function() {
    $html = \craft\helpers\Cp::entryTypeSelectHtml([
        'id' => 'types',
        'name' => 'entryTypes[]',
        'options' => [],
        'values' => [],
        'jsClass' => 'Craft.EntryTypeSelectInput',
    ]);

    expect($html)
        ->toContain('class="componentselect"')
        ->not->toContain('<craft-component-select');
});

it('selects the boolean menu option for the legacy value', function(string $value, string $expected) {
    expect(renderCpString("{% include '_includes/forms/booleanMenu' with {id: 'bm', name: 'enabled', value: $value} only %}"))
        ->toContain("model-value=\"$expected\"");
})->with([
    'true' => ['true', '1'],
    'false' => ['false', '0'],
    'integer' => ['1', '1'],
    'string one' => ["'1'", '1'],
    'string zero' => ["'0'", '0'],
]);

it('renders the selectize wrappers as comboboxes', function(string $include) {
    expect(renderCpString($include))->toContain('<craft-combobox');
})->with([
    'languageMenu' => ["{% include '_includes/forms/languageMenu' with {name: 'lang', options: [{label: 'English', value: 'en'}]} only %}"],
    'timeZone' => ["{% include '_includes/forms/timeZone' with {name: 'tz'} only %}"],
    'volume' => ["{% include '_includes/forms/volume' with {name: 'volume', options: []} only %}"],
    'colorSelect' => ["{% include '_includes/forms/colorSelect' with {name: 'color'} only %}"],
]);

it('renders select, money and icon picker through their components', function(string $include, string $tag) {
    expect(renderCpString($include))->toContain("<$tag");
})->with([
    'select' => ["{% include '_includes/forms/select' with {name: 's', options: {a: 'A'}} only %}", 'craft-select'],
    'money' => ["{% include '_includes/forms/money' with {name: 'price', value: 5, currency: 'EUR'} only %}", 'craft-input-money'],
    'iconPicker' => ["{% include '_includes/forms/iconPicker' with {name: 'icon'} only %}", 'craft-icon-picker'],
]);
