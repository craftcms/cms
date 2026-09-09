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

it('renders the map screen as a Vue page', function () {
    $this->get(action([ImportConfigController::class, 'editMap'], ['handle' => $this->importer->handle]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('import/configs/Map')
            ->where('config.uid', $this->importer->uid)
            ->where('config.handle', $this->importer->handle)
            ->where('values.map', [])
            ->where('canSave', true)
            ->has('destinationCols')
            ->has('nestedColsUrl')
        );
});

it('returns a container field’s destination columns for the nested mapping panel', function () {
    $response = $this->getJson(action([ImportConfigController::class, 'nestedMappingCols'], [
        'fieldUid' => $this->outerMatrixField->uid,
        'importUid' => $this->importer->uid,
        'fieldHandle' => 'outerMatrix',
    ]));

    $response->assertOk();
    expect($response->json('fieldName'))->toBe('Outer Matrix');
    expect($response->json('groups.0.providerName'))->toBe('Outer ET');

    // The columns are addressed from the root of the mapping trees, prefixed with the
    // container's own handle — that's what lets the panel edit the page's state in place.
    $handles = array_column($response->json('groups.0.destinationCols'), 'prefixedHandle');
    expect($handles)->toContain('outerMatrix[outerEt][fields][innerMatrix]');
});

it('persists both levels’ keepMissingNestedElements decisions when the map is saved', function () {
    // The nested panel writes into the page's trees client-side, so the whole nested
    // shape arrives in one storeMap post — there's no per-container round trip.
    $this->postJson(action([ImportConfigController::class, 'storeMap']), [
        'importUid' => $this->importer->uid,
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

it('persists match criteria and clearable items alongside the map', function () {
    $this->postJson(action([ImportConfigController::class, 'storeMap']), [
        'importUid' => $this->importer->uid,
        'map' => ['title' => 'Name'],
        'matchCriteria' => ['title' => '1'],
        'clearableItems' => ['title' => '1'],
    ])->assertOk();

    $saved = app(ImportConfig::class)->getConfigByUid($this->importer->uid);

    expect($saved->matchCriteria)->toBe(['title' => 1]);
    expect($saved->clearableItems)->toBe(['title' => 1]);
});

it('still decodes JSON-encoded container branches on save', function () {
    // File-based configs and plugins can still post the shape the Twig screen's hidden
    // inputs produced.
    $this->postJson(action([ImportConfigController::class, 'storeMap']), [
        'importUid' => $this->importer->uid,
        'map' => ['outerMatrix' => json_encode(['outerEt' => ['title' => 'Title']])],
    ])->assertOk();

    $saved = app(ImportConfig::class)->getConfigByUid($this->importer->uid);

    expect($saved->map)->toBe([
        'outerMatrix' => ['outerEt' => ['title' => 'Title']],
    ]);
});
