<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Import\ElementImporter;
use CraftCms\Cms\Element\Import\ElementTransformer;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Import\EntryImporter;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\ImportPlan;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

it('getSettingsRules covers a step’s settings while getRules adds its source and transformer', function () {
    $settingsRules = ElementImporter::getSettingsRules();
    $fullRules = ElementImporter::getRules();

    expect($settingsRules)->not->toHaveKey('source')
        ->and($settingsRules)->not->toHaveKey('transformer')
        ->and($settingsRules)->toHaveKeys(['settings.map', 'settings.site'])
        ->and($fullRules)->toHaveKeys(['source', 'transformer', 'settings.map', 'settings.site']);
});

it('keeps the import’s own name and handle off the step rules', function () {
    expect(ElementImporter::getRules())->not->toHaveKey('name')
        ->and(ElementImporter::getRules())->not->toHaveKey('handle')
        ->and(new ImportPlanData()->getRules())->toHaveKeys(['name', 'handle', 'steps']);
});

it('validateSettings throws with settings-only errors for an ad-hoc importer missing source/site', function () {
    $importer = EntryImporter::create();

    try {
        $importer->validateSettings();
        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->not->toHaveKey('source')
            ->and($e->errors())->toHaveKey('settings.site')
            ->and($e->errors())->not->toHaveKey('name')
            ->and($e->errors())->not->toHaveKey('handle');
    }
});

it('validate throws for a step missing its source', function () {
    $importer = EntryImporter::create();

    try {
        $importer->validate();
        expect(false)->toBeTrue('Expected a ValidationException to be thrown.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('source')
            ->and($e->errors())->toHaveKey('settings.site');
    }
});

it('validateSite fails for an invalid handle', function () {
    $errors = Validator::make([
        'settings' => ['site' => 'nonexistent-handle'],
    ], ['settings.site' => ElementImporter::getSettingsRules()['settings.site']])->errors();

    expect($errors)->toHaveKey('settings.site');
});

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

it('validateFieldLayout fails for a field layout that does not exist', function () {
    $errors = Validator::make([
        'uid' => 'step-uid',
        'settings' => ['fieldLayout' => 'not-a-field-layout'],
    ], ['settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout']])->errors();

    expect($errors)->toHaveKey('settings.fieldLayout');
});

it('validateFieldLayout still passes the existence check when className is missing', function () {
    $fieldLayout = FieldLayout::factory()->create(['type' => EntryElement::class]);
    Fields::refreshFields();

    $errors = Validator::make([
        'settings' => ['fieldLayout' => $fieldLayout->uid],
    ], ['settings.fieldLayout' => ElementImporter::getSettingsRules()['settings.fieldLayout']])->errors();

    expect($errors)->not->toHaveKey('settings.fieldLayout');
});

it('validateTransformer passes for a transformer class and an empty value, and fails for anything else', function () {
    $rule = ElementImporter::getRules()['transformer'];

    $passing = Validator::make(['transformer' => null], ['transformer' => $rule])->errors();
    $passingClass = Validator::make(['transformer' => ElementTransformer::class], ['transformer' => $rule])->errors();
    $failingArrowFn = Validator::make(['transformer' => 'fn ($element) => $element'], ['transformer' => $rule])->errors();
    $failingClass = Validator::make(['transformer' => stdClass::class], ['transformer' => $rule])->errors();
    $failing = Validator::make(['transformer' => 'NotARealClass'], ['transformer' => $rule])->errors();

    expect($passing)->not->toHaveKey('transformer')
        ->and($passingClass)->not->toHaveKey('transformer')
        ->and($failingArrowFn)->toHaveKey('transformer')
        ->and($failingClass)->toHaveKey('transformer')
        ->and($failing)->toHaveKey('transformer');
});

it('excludes an invalid file-based import from getAllImportPlans', function () {
    Config::set('craft.import', [
        'invalidFileImport' => fn () => new ImportPlanData()
            ->name('Invalid File Import')
            ->handle('invalidFileImport')
            ->steps([]),
    ]);

    $imports = app(ImportPlan::class);

    expect($imports->getAllImportPlans()->has('invalidFileImport'))->toBeFalse()
        ->and($imports->getNonEditableImportPlans()->has('invalidFileImport'))->toBeFalse()
        ->and($imports->getImportPlanByHandle('invalidFileImport'))->toBeNull();
});

it('excludes only the file-based import whose source alias isn’t defined', function () {
    Config::set('craft.import', [
        'badAlias' => fn () => new ImportPlanData()
            ->name('Bad Alias')
            ->handle('badAlias')
            ->steps([['type' => EntryImporter::class, 'source' => '@nope/entries.json']]),
    ]);

    $imports = app(ImportPlan::class);

    expect(fn () => $imports->getAllImportPlans())->not->toThrow(Throwable::class)
        ->and($imports->getAllImportPlans()->has('badAlias'))->toBeFalse();
});

it('reports a step without a source instead of failing', function () {
    $importPlan = new ImportPlanData()
        ->name('No Source')
        ->handle('noSource')
        ->steps([['type' => EntryImporter::class]]);

    expect($importPlan->validate())->toBeFalse()
        ->and($importPlan->errors()->keys())->toContain('steps.'.$importPlan->steps[0]->uid.'.source');
});

it('leaves out a step whose importer can’t be created instead of failing', function () {
    $importPlan = new ImportPlanData()
        ->name('Bad Type')
        ->handle('badType')
        ->steps([['type' => 'NotARealImporter', 'source' => 'entries.csv']]);

    expect($importPlan->steps)->toBe([]);
});

it('doesn’t resolve URL hostnames when validating a file-based import', function () {
    $step = new EntryImporter(['type' => EntryImporter::class, 'source' => 'https://unresolvable.invalid/entries.json']);

    $fileBased = new ImportPlanData()->name('Remote')->handle('remote')->steps([$step]);
    $editable = new ImportPlanData(['editable' => true])->name('Remote')->handle('remote')->steps([$step]);

    $fileBased->validate();
    $editable->validate();

    expect($fileBased->errors()->keys())->not->toContain("steps.{$step->uid}.source")
        ->and($editable->errors()->keys())->toContain("steps.{$step->uid}.source");
});
