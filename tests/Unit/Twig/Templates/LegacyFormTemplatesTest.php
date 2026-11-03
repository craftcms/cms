<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateResolver;

it('leaves the legacy form templates to the yii2-adapter', function (string $template) {
    expect(app(TemplateResolver::class)->resolve($template, TemplateMode::Cp))->toBeFalse();
})->with([
    '_includes/forms',
    '_includes/forms/autosuggest',
    '_includes/forms/booleanMenu',
    '_includes/forms/button',
    '_includes/forms/buttonGroup',
    '_includes/forms/checkbox',
    '_includes/forms/checkboxGroup',
    '_includes/forms/checkboxSelect',
    '_includes/forms/color',
    '_includes/forms/colorSelect',
    '_includes/forms/componentSelect',
    '_includes/forms/copytext',
    '_includes/forms/customSelect',
    '_includes/forms/date',
    '_includes/forms/datetime',
    '_includes/forms/editableTable',
    '_includes/forms/elementSelect',
    '_includes/forms/entryTypeSelect',
    '_includes/forms/field',
    '_includes/forms/iconPicker',
    '_includes/forms/lightswitch',
    '_includes/forms/money',
    '_includes/forms/multiselect',
    '_includes/forms/password',
    '_includes/forms/radioGroup',
    '_includes/forms/range',
    '_includes/forms/select',
    '_includes/forms/selectize',
    '_includes/forms/text',
    '_includes/forms/textarea',
    '_includes/forms/time',
    '_includes/forms/timeZone',
    '_components/fieldtypes/Assets/input',
]);

it('ships no legacy form templates', function () {
    expect(glob(Path::cpTemplates('_includes/forms').'/*') ?: [])->toBe([]);
});
