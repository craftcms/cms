<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Data\Import as ImportData;
use CraftCms\Cms\Import\Imports;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

it('getSettingsRules covers a step’s settings while getRules adds its file and transformer', function () {
    $settingsRules = ElementImporter::getSettingsRules();
    $fullRules = ElementImporter::getRules();

    expect($settingsRules)->not->toHaveKey('file')
        ->and($settingsRules)->not->toHaveKey('transformer')
        ->and($settingsRules)->toHaveKeys(['settings.map', 'settings.site'])
        ->and($fullRules)->toHaveKeys(['file', 'transformer', 'settings.map', 'settings.site']);
});

it('keeps the import’s own name and handle off the step rules', function () {
    expect(ElementImporter::getRules())->not->toHaveKey('name')
        ->and(ElementImporter::getRules())->not->toHaveKey('handle')
        ->and(new ImportData()->getRules())->toHaveKeys(['name', 'handle', 'steps']);
});

it('validateSettings throws with settings-only errors for an ad-hoc importer missing file/site', function () {
    $importer = EntryImporter::create();

    try {
        $importer->validateSettings();
        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->not->toHaveKey('file')
            ->and($e->errors())->toHaveKey('settings.site')
            ->and($e->errors())->not->toHaveKey('name')
            ->and($e->errors())->not->toHaveKey('handle');
    }
});

it('validate throws for a step missing its file', function () {
    $importer = EntryImporter::create();

    try {
        $importer->validate();
        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('file')
            ->and($e->errors())->toHaveKey('settings.site');
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
        'settings' => ['fieldLayout' => $fieldLayout->uid],
    ], [
        'settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout'],
    ])->errors();

    expect($errors)->toHaveKey('settings.fieldLayout');
})->skip('Revisit after the list of changes from the meeting on 15.09 is actioned.');

it('validateFieldLayout passes when the layout matches the element type', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $errors = Validator::make([
        'settings' => ['fieldLayout' => $fieldLayout->uid],
    ], [
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
    $rule = ElementImporter::getRules()['transformer'];

    $passing = Validator::make(['transformer' => null], ['transformer' => $rule])->errors();
    $passingClass = Validator::make(['transformer' => ElementTransformer::class], ['transformer' => $rule])->errors();
    $passingArrowFn = Validator::make(['transformer' => 'fn ($element) => $element'], ['transformer' => $rule])->errors();
    $failing = Validator::make(['transformer' => 'NotARealClass'], ['transformer' => $rule])->errors();

    expect($passing)->not->toHaveKey('transformer')
        ->and($passingClass)->not->toHaveKey('transformer')
        ->and($passingArrowFn)->not->toHaveKey('transformer')
        ->and($failing)->toHaveKey('transformer');
});

it('excludes an invalid file-based import from getAllImports', function () {
    Config::set('craft.import', [
        'invalidFileImport' => fn () => new ImportData()
            ->name('Invalid File Import')
            ->handle('invalidFileImport')
            ->steps([]),
    ]);

    $imports = app(Imports::class);

    expect($imports->getAllImports()->has('invalidFileImport'))->toBeFalse()
        ->and($imports->getNonEditableImports()->has('invalidFileImport'))->toBeFalse()
        ->and($imports->getImportByHandle('invalidFileImport'))->toBeNull();
});
