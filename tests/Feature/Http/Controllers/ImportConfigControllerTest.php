<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Import\ImportConfigController;
use CraftCms\Cms\Import\ImportConfig;
use CraftCms\Cms\Import\Importers\ElementImporter;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::find()->one());

    $innerPlainTextField = Field::factory()->create([
        'name' => 'Inner Plain Text',
        'handle' => 'innerPlainText',
        'type' => PlainText::class,
    ]);

    $innerEntryType = EntryType::factory()
        ->withField($innerPlainTextField)
        ->create(['name' => 'Inner ET', 'handle' => 'innerEt', 'hasTitleField' => true]);

    $innerMatrixField = Field::factory()->create([
        'name' => 'Inner Matrix',
        'handle' => 'innerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$innerEntryType->id]],
    ]);

    Fields::refreshFields();

    $outerEntryType = EntryType::factory()
        ->withFieldLayout(
            FieldLayout::factory()
                ->withContentTab([
                    new EntryTitleField(['uid' => Str::uuid()->toString(), 'required' => true]),
                    CustomField::make($innerMatrixField->handle),
                ])
                ->create()
        )
        ->create(['name' => 'Outer ET', 'handle' => 'outerEt', 'hasTitleField' => true]);

    $this->outerMatrixField = Field::factory()->create([
        'name' => 'Outer Matrix',
        'handle' => 'outerMatrix',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$outerEntryType->id]],
    ]);

    EntryTypes::refreshEntryTypes();
    Fields::refreshFields();

    $importer = ElementImporter::create()
        ->className(EntryElement::class)
        ->site(Sites::getPrimarySite()->handle)
        ->transformer(null);
    $importer->name('Matrix in Matrix Import');
    $importer->handle('matrixInMatrixImport');

    app(ImportConfig::class)->saveConfig($importer);

    $this->importer = $importer;
});

it('returns a container field’s whole keepMissingNestedElements branch, not just its own leaf', function () {
    $response = $this->postJson(action([ImportConfigController::class, 'storeNestedFieldMapping']), [
        'fieldUid' => $this->outerMatrixField->uid,
        'importUid' => $this->importer->uid,
        'fieldHandle' => 'outerMatrix',
        'map' => ['outerMatrix' => ['outerEt' => []]],
        'keepMissingNestedElements' => [
            'outerMatrix' => [
                '__keep__' => '1',
                'outerEt' => [
                    'fields' => [
                        'innerMatrix' => ['__keep__' => '1'],
                    ],
                ],
            ],
        ],
    ]);

    $response->assertOk();
    expect($response->json('keepMissingNestedElements'))->toBe([
        '__keep__' => 1,
        'outerEt' => [
            'fields' => [
                'innerMatrix' => ['__keep__' => 1],
            ],
        ],
    ]);
});

it('persists both levels’ keepMissingNestedElements decisions after saving the outer map', function () {
    $nested = $this->postJson(action([ImportConfigController::class, 'storeNestedFieldMapping']), [
        'fieldUid' => $this->outerMatrixField->uid,
        'importUid' => $this->importer->uid,
        'fieldHandle' => 'outerMatrix',
        'map' => ['outerMatrix' => ['outerEt' => []]],
        'keepMissingNestedElements' => [
            'outerMatrix' => [
                '__keep__' => '1',
                'outerEt' => [
                    'fields' => [
                        'innerMatrix' => ['__keep__' => '1'],
                    ],
                ],
            ],
        ],
    ])->json('keepMissingNestedElements');

    $this->postJson(action([ImportConfigController::class, 'storeMap']), [
        'importUid' => $this->importer->uid,
        'map' => ['outerMatrix' => ['outerEt' => []]],
        'keepMissingNestedElements' => [
            'outerMatrix' => $nested,
        ],
    ])->assertOk();

    $saved = app(ImportConfig::class)->getConfigByUid($this->importer->uid);

    expect($saved->keepMissingNestedElements)->toBe([
        'outerMatrix' => [
            '__keep__' => 1,
            'outerEt' => [
                'fields' => [
                    'innerMatrix' => ['__keep__' => 1],
                ],
            ],
        ],
    ]);
});
