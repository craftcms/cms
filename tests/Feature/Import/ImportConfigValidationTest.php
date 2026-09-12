<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\ImportConfig;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Import\Transformers\ElementTransformer;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

it('getSettingsRules excludes name/handle while getRules includes them', function () {
    $settingsRules = ElementImporter::getSettingsRules();
    $fullRules = ElementImporter::getRules();

    expect($settingsRules)->not->toHaveKey('name')
        ->and($settingsRules)->not->toHaveKey('handle')
        ->and($settingsRules)->toHaveKeys(['settings.file', 'settings.className', 'settings.transformer', 'settings.map', 'settings.site'])
        ->and($fullRules)->toHaveKeys(['name', 'handle', 'settings.file', 'settings.className', 'settings.transformer', 'settings.map', 'settings.site']);
});

it('validateSettings throws with settings-only errors for an ad-hoc importer missing file/site, even without a name/handle', function () {
    $importer = ElementImporter::create()->className(EntryElement::class);

    try {
        $importer->validateSettings();
        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('settings.file')
            ->and($e->errors())->toHaveKey('settings.site')
            ->and($e->errors())->not->toHaveKey('name')
            ->and($e->errors())->not->toHaveKey('handle');
    }
});

it('validate throws for an invalid importer, including missing name/handle', function () {
    $importer = ElementImporter::create()->className(EntryElement::class);

    try {
        $importer->validate();
        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('name')
            ->and($e->errors())->toHaveKey('handle')
            ->and($e->errors())->toHaveKey('settings.file');
    }
});

it('validateSite fails for an invalid handle', function () {
    $errors = Validator::make([
        'settings' => ['site' => 'nonexistent-handle'],
    ], ['settings.site' => ElementImporter::getSettingsRules()['settings.site']])->errors();

    expect($errors)->toHaveKey('settings.site');
});

it('validateFieldLayout fails and creates no field layout row when given a nonexistent UID/type', function () {
    $countBefore = FieldLayout::query()->count();

    $errors = Validator::make([
        'uid' => 'some-config-uid',
        'settings' => ['fieldLayout' => 'not-a-real-uid-or-type'],
    ], ['settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout']])->errors();

    expect($errors)->toHaveKey('settings.fieldLayout')
        ->and(FieldLayout::query()->count())->toBe($countBefore);
});

it('validateFieldLayout fails when the layout belongs to a different element type', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => Address::class]);
    Fields::refreshFields();

    $errors = Validator::make([
        'uid' => 'some-config-uid',
        'settings' => ['className' => EntryElement::class, 'fieldLayout' => $fieldLayout->uid],
    ], [
        'settings.className' => ElementImporter::getSettingsRules()['settings.className'],
        'settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout'],
    ])->errors();

    expect($errors)->toHaveKey('settings.fieldLayout');
});

it('validateFieldLayout passes when the layout matches the element type', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $errors = Validator::make([
        'settings' => ['className' => EntryElement::class, 'fieldLayout' => $fieldLayout->uid],
    ], [
        'settings.className' => ElementImporter::getSettingsRules()['settings.className'],
        'settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout'],
    ])->errors();

    expect($errors)->not->toHaveKey('settings.fieldLayout');
});

it('validateFieldLayout still passes the existence check when className is missing', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $errors = Validator::make([
        'settings' => ['fieldLayout' => $fieldLayout->uid],
    ], ['settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout']])->errors();

    expect($errors)->not->toHaveKey('settings.fieldLayout');
});

it('validateTransformer still passes for a valid class, arrow function, and empty value, and fails for garbage', function () {
    $rule = ElementImporter::getSettingsRules()['settings.transformer'];

    $passing = Validator::make(['settings' => ['transformer' => null]], ['settings.transformer' => $rule])->errors();
    $passingClass = Validator::make(['settings' => ['transformer' => ElementTransformer::class]], ['settings.transformer' => $rule])->errors();
    $passingArrowFn = Validator::make(['settings' => ['transformer' => 'fn ($element) => $element']], ['settings.transformer' => $rule])->errors();
    $failing = Validator::make(['settings' => ['transformer' => 'NotARealClass']], ['settings.transformer' => $rule])->errors();

    expect($passing)->not->toHaveKey('settings.transformer')
        ->and($passingClass)->not->toHaveKey('settings.transformer')
        ->and($passingArrowFn)->not->toHaveKey('settings.transformer')
        ->and($failing)->toHaveKey('settings.transformer');
});

it('excludes an invalid file-based import config from getAllConfigs', function () {
    Config::set('craft.import', [
        'invalidFileConfig' => fn () => ElementImporter::create()
            ->name('Invalid File Config')
            ->handle('invalidFileConfig')
            ->className(EntryElement::class),
    ]);

    $importConfig = app(ImportConfig::class);

    expect($importConfig->getAllConfigs()->has('invalidFileConfig'))->toBeFalse()
        ->and($importConfig->getNonEditableConfigs()->has('invalidFileConfig'))->toBeFalse()
        ->and($importConfig->getConfigByHandle('invalidFileConfig'))->toBeNull();
});
