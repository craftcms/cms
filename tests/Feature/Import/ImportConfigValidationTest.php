<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Import\ImportConfig;
use CraftCms\Cms\Import\Importers\ElementImporter;
use Illuminate\Support\Facades\Config;
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
