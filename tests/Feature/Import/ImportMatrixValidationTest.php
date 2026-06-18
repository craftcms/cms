<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Fields as FieldsService;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

beforeEach(function () {
    $plainTextField = Field::factory()->create([
        'name' => 'Plain Text',
        'handle' => 'plainText',
        'type' => PlainText::class,
    ]);

    $this->firstEntryType = EntryType::factory()
        ->withField($plainTextField)
        ->create(['name' => 'First ET', 'handle' => 'firstEt']);

    $this->secondEntryType = EntryType::factory()
        ->withField($plainTextField)
        ->create(['name' => 'Second ET', 'handle' => 'secondEt']);

    $matrixFieldModel = Field::factory()->create([
        'name' => 'My Matrix',
        'handle' => 'myMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$this->firstEntryType->id, $this->secondEntryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $this->matrixField = app(FieldsService::class)->getFieldByHandle('myMatrix');
    $this->validator = ValidatorFacade::make([], []);
});

it('validateMapping returns true when all entry types in the map are allowed', function () {
    $failCalled = false;
    $fail = function () use (&$failCalled) {
        $failCalled = true;
    };

    $value = [
        'type' => [
            'firstEt' => ['map' => ['plainText' => 'plainText']],
            'secondEt' => ['map' => ['plainText' => 'plainText']],
        ],
    ];

    $result = $this->matrixField->validateMapping($value, 'map', $fail, $this->validator, ['field' => $this->matrixField]);

    expect($result)->toBeTrue();
    expect($failCalled)->toBeFalse();
});

it('validateMapping returns false and calls $fail when the map references a disallowed entry type', function () {
    $failMessages = [];
    $fail = function (string $attr, string $msg) use (&$failMessages) {
        $failMessages[] = $msg;
    };

    $value = [
        'type' => [
            'firstEt' => ['map' => ['plainText' => 'plainText']],
            'notAllowed' => ['map' => ['plainText' => 'plainText']],
        ],
    ];

    $result = $this->matrixField->validateMapping($value, 'map', $fail, $this->validator, ['field' => $this->matrixField]);

    expect($result)->toBeFalse();
    expect($failMessages)->not()->toBeEmpty();
});

it('ElementImporter::validateMap returns true when no field param is given', function () {
    $failCalled = false;
    $fail = function () use (&$failCalled) {
        $failCalled = true;
    };

    $result = ElementImporter::validateMap(['type' => []], 'map', $fail, $this->validator);

    expect($result)->toBeTrue();
    expect($failCalled)->toBeFalse();
});

it('ElementImporter::validateMap calls $fail via the field when an entry type is not allowed', function () {
    $failMessages = [];
    $fail = function (string $attr, string $msg) use (&$failMessages) {
        $failMessages[] = $msg;
    };

    $value = [
        'type' => [
            'notAllowed' => ['map' => []],
        ],
    ];

    // validateMap always returns true; it surfaces errors through $fail
    $result = ElementImporter::validateMap($value, 'map', $fail, $this->validator, ['field' => $this->matrixField]);

    expect($result)->toBeTrue();
    expect($failMessages)->not()->toBeEmpty();
});
