<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

function createFieldLayoutConfig(Field|FieldInterface $field, bool $required = false): array
{
    return [
        'tabs' => [
            [
                'uid' => Str::uuid()->toString(),
                'name' => 'Content',
                'elements' => [
                    [
                        'uid' => Str::uuid()->toString(),
                        'type' => CustomField::class,
                        'fieldUid' => $field->uid,
                        'required' => $required,
                    ],
                ],
            ],
        ],
    ];
}

function createStoreEntryMatrixEntryType(Field $field): EntryType
{
    return EntryType::factory()
        ->withField($field)
        ->create([
            'name' => 'Matrix Block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);
}

function createContentBlockSettings(Field $field): array
{
    $layoutUid = Str::uuid()->toString();

    return [
        'fieldLayouts' => [
            $layoutUid => createFieldLayoutConfig($field),
        ],
    ];
}

beforeEach(function () {
    $this->user = User::factory()->admin()->create();
    actingAs($this->user);

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
    ]);
});

it('requires login', function () {
    Auth::logout();

    post(action(StoreEntryController::class))
        ->assertRedirectContains('/login');
});

it('requires sectionId when creating a new entry', function () {
    post(action(StoreEntryController::class), [
        // missing sectionId
    ])->assertInvalid(['sectionId']);
});

it('can create a new entry', function () {
    $data = [
        'sectionId' => $this->section->id,
        'title' => 'My New Entry',
        'slug' => 'my-new-entry',
        'enabled' => true,
    ];

    post(action(StoreEntryController::class), $data)
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('elements_sites', [
        'slug' => 'my-new-entry',
    ]);

    // Check elements table for title
    $entry = Entry::find()->slug('my-new-entry')->status(null)->one();
    expect($entry)->not->toBeNull()
        ->and($entry->title)->toBe('My New Entry')
        ->and($entry->sectionId)->toBe($this->section->id)
        ->and($entry->authorId)->toBe($this->user->id);
});

it('can update an existing entry', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'title' => 'Updated Title',
    ])->assertRedirect();

    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    expect($entry->title)->toBe('Updated Title');
});

it('clears existing plain text field values', function () {
    $field = Field::factory()->create([
        'name' => 'Body Field',
        'handle' => 'bodyField',
        'type' => PlainText::class,
    ]);

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => createFieldLayoutConfig($field),
    ]);

    $this->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();
    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    $entry->setFieldValue($field->handle, 'Existing value');
    Elements::saveElement($entry);

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'fields' => [
            'bodyField' => '',
        ],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    expect($entry->getFieldValue('bodyField'))->toBeNull();
});

it('clears existing entries field values', function () {
    $targetEntry = EntryModel::factory()->create();
    $field = Field::factory()->create([
        'name' => 'Related Entries',
        'handle' => 'relatedEntries',
        'type' => EntriesField::class,
    ]);

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => createFieldLayoutConfig($field),
    ]);

    $this->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();
    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    $entry->setFieldValue($field->handle, [$targetEntry->id]);
    Elements::saveElement($entry);

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'fields' => [
            'relatedEntries' => '',
        ],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    expect($entry->getFieldValue('relatedEntries')->ids())->toBe([]);
});

it('clears existing native entry attributes', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create([
        'expiryDate' => now()->addDay(),
    ]);

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'expiryDate' => '',
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = Entry::find()->id($entryModel->id)->status(null)->one();
    expect($entry->expiryDate)->toBeNull();
});

it('can duplicate an entry', function (bool $enabled, bool $postedEnabled, bool $provisional, bool $json, bool $uriConflict) {
    $field = Field::factory()->create(['handle' => 'bodyField', 'type' => PlainText::class]);
    $layout = FieldLayout::create(['type' => Entry::class, 'config' => createFieldLayoutConfig($field)]);
    $this->entryType->update(['fieldLayoutId' => $layout->id]);
    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    if ($uriConflict) {
        SectionSiteSettings::where('sectionId', $this->section->id)->update(['hasUrls' => true, 'uriFormat' => 'fixed-uri']);
    }

    $source = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->createElement([
        'title' => 'Original title',
        'slug' => 'original-slug',
    ]);
    $source->enabled = $enabled;
    $source->setFieldValue('bodyField', 'Original body');
    expect(Elements::saveElement($source))->toBeTrue();

    if ($provisional) {
        $draft = app(Drafts::class)->createDraft($source, $this->user->id);
        $draft->isProvisionalDraft = true;
        $draft->setFieldValue('bodyField', 'Draft body');
        expect(Elements::saveElement($draft))->toBeTrue();
    }

    $data = [
        'entryId' => $source->id,
        'duplicate' => true,
        'provisional' => $provisional,
        'title' => 'Copy title',
        'slug' => 'copy-slug',
        'enabled' => $postedEnabled,
        'fields' => ['bodyField' => 'Copy body'],
        'redirect' => Crypt::encrypt('/entries/{id}'),
    ];
    $response = $json ? postJson(action(StoreEntryController::class), $data) : post(action(StoreEntryController::class), $data, ['Accept' => 'text/html']);
    if ($json) {
        $response->assertOk();
    }
    $copy = Entry::find()->status(null)->id(['not', $source->id])->one();

    expect($copy)->not->toBeNull();
    expect($copy->title)->toBe('Copy title');
    expect($copy->slug)->toBe('copy-slug');
    expect($copy->enabled)->toBe($uriConflict ? false : $postedEnabled);
    expect($copy->getFieldValue('bodyField'))->toBe('Copy body');
    expect(Entry::find()->status(null)->count())->toBe(2);

    if ($json) {
        $response->assertOk()->assertJsonPath('id', $copy->id)->assertJsonPath('modelId', $copy->id)
            ->assertJsonPath('redirect', '/entries/'.$copy->id);
    } else {
        $response->assertRedirect('/entries/'.$copy->id)->assertSessionHas('modelId', $copy->id);
    }

    $original = Entry::find()->id($source->id)->status(null)->one();
    expect($original->title)->toBe('Original title');
    expect($original->slug)->toBe('original-slug');
    expect($original->enabled)->toBe($enabled);
    expect($original->getFieldValue('bodyField'))->toBe('Original body');

    if ($provisional) {
        $originalDraft = Entry::find()->provisionalDrafts()->id($draft->id)->status(null)->one();
        expect($originalDraft->isProvisionalDraft)->toBeTrue();
        expect($originalDraft->getFieldValue('bodyField'))->toBe('Draft body');
        expect($copy->draftId)->toBeNull();
        expect($copy->isProvisionalDraft)->toBeFalse();
    }
})->with([
    'disable copy' => [true, false, false, true, false],
    'enable copy' => [false, true, false, true, false],
    'redirect to copy' => [true, false, false, false, false],
    'provisional source' => [true, false, true, true, false],
    'URI conflict keeps copy disabled' => [true, true, false, true, true],
]);

it('returns the correct entry when duplication or saving fails', function (bool $duringDuplication, bool $json) {
    $source = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->createElement([
        'title' => 'Original title',
    ]);
    $failedEntry = null;
    Event::listen(ElementSaving::class, function (ElementSaving $event) use ($duringDuplication, &$failedEntry) {
        if (! $event->element instanceof Entry || $event->element->title !== ($duringDuplication ? 'Original title' : 'Copy title')) {
            return;
        }

        $failedEntry = $event->element;
        $event->element->errors()->add('title', 'Save rejected');
        $event->isValid = false;
    });

    $data = [
        'entryId' => $source->id,
        'duplicate' => true,
        'title' => 'Copy title',
        'entryVariable' => Crypt::encrypt('editedEntry'),
    ];
    $response = $json ? postJson(action(StoreEntryController::class), $data) : post(action(StoreEntryController::class), $data, ['Accept' => 'text/html']);
    expect($failedEntry)->not->toBeNull();
    $modelName = $duringDuplication ? ($json ? 'model' : 'entry') : 'editedEntry';
    $expectedId = $duringDuplication && ! $json ? $source->id : $failedEntry->id;

    if ($json) {
        $response->assertBadRequest()->assertJsonPath('modelName', $modelName)
            ->assertJsonPath($modelName.'.id', $expectedId)->assertJsonPath('errors.title', ['Save rejected']);
    } else {
        $response->assertRedirect()->assertSessionHas($modelName.'.id', $expectedId)->assertSessionHasErrors('title');
    }

    expect(Entry::find()->id($source->id)->status(null)->one()->title)->toBe('Original title');
    expect(Entry::find()->status(null)->count())->toBe($duringDuplication ? 1 : 2);
    if (! $duringDuplication) {
        expect($failedEntry->id)->not->toBe($source->id);
        expect(Entry::find()->id($failedEntry->id)->status(null)->one()->title)->toBe('Original title');
    }
})->with([true, false])->with([true, false]);

it('handles provisional drafts', function () {
    // 1. Create a live entry
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    // 2. Create a provisional draft for this entry
    $liveEntry = Entry::find()->id($entryModel->id)->status(null)->one();
    $draft = app(Drafts::class)->createDraft($liveEntry, $this->user->id);
    $draft->isProvisionalDraft = true;
    Elements::saveElement($draft);

    expect($draft->isProvisionalDraft)->toBeTrue();

    // Save the LIVE entry
    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'title' => 'Updated Live Entry',
    ])->assertRedirect();

    // The provisional draft should be gone
    $draftCheck = Entry::find()->drafts(true)->id($draft->id)->one();
    expect($draftCheck)->toBeNull();
});

it('returns JSON response', function () {
    $data = [
        'sectionId' => $this->section->id,
        'title' => 'JSON Entry',
        'slug' => 'json-entry',
        'enabled' => true,
    ];

    postJson(action(StoreEntryController::class), $data)
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'title',
            'slug',
            'dateCreated',
            'dateUpdated',
        ])
        ->assertJsonFragment([
            'title' => 'JSON Entry',
            'slug' => 'json-entry',
        ]);
});

it('throws exception when entry is locked', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    // Mock Cache::lock to return false (lock acquired by someone else)
    Cache::shouldReceive('lock')
        ->with("entry:{$entryModel->id}", 15)
        ->andReturn(
            Mockery::mock(Lock::class)
                ->shouldReceive('get')
                ->andReturn(false)
                ->getMock()
        );

    $this->withoutExceptionHandling();
    $this->expectException(LockTimeoutException::class);

    post(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'title' => 'Locked Entry Update',
    ]);
});

it('handles 404 for missing entry', function () {
    post(action(StoreEntryController::class), [
        'entryId' => 999999,
        'title' => 'Ghost Entry',
    ])->assertNotFound();
});

it('persists nested matrix field values when creating an entry', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $matrixEntryType = createStoreEntryMatrixEntryType($innerField);
    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => createFieldLayoutConfig($matrixField),
    ]);

    $this->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    $blockUid = Str::uuid()->toString();

    post(action(StoreEntryController::class), [
        'sectionId' => $this->section->id,
        'title' => 'Matrix Entry',
        'slug' => 'matrix-entry',
        'fields' => [
            'matrixField' => [
                'entries' => [
                    "uid:$blockUid" => [
                        'type' => $matrixEntryType->handle,
                        'title' => 'Block 1',
                        'enabled' => true,
                        'fields' => [
                            'innerText' => 'Nested matrix value',
                        ],
                    ],
                ],
                'sortOrder' => [$blockUid],
            ],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = Entry::find()->slug('matrix-entry')->status(null)->one();
    $matrixEntry = $entry->getFieldValue('matrixField')->status(null)->one();

    expect($entry->getFieldValue('matrixField')->getResultOverride())->toBeNull();

    expect($entry)->not->toBeNull()
        ->and($matrixEntry)->not->toBeNull()
        ->and($matrixEntry->getFieldValue('innerText'))->toBe('Nested matrix value');
});

it('persists nested content block field values when creating an entry', function () {
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $contentBlockField = Fields::createField([
        'name' => 'Content Block',
        'handle' => 'contentBlock',
        'type' => ContentBlock::class,
        'settings' => createContentBlockSettings($innerField),
    ]);

    expect(Fields::saveField($contentBlockField))->toBeTrue();

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => createFieldLayoutConfig($contentBlockField),
    ]);

    $this->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    post(action(StoreEntryController::class), [
        'sectionId' => $this->section->id,
        'title' => 'Content Block Entry',
        'slug' => 'content-block-entry',
        'fields' => [
            'contentBlock' => [
                'fields' => [
                    'innerText' => 'Nested content block value',
                ],
            ],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = Entry::find()->slug('content-block-entry')->status(null)->one();
    $contentBlock = $entry->getFieldValue('contentBlock');
    $innerLayoutField = $contentBlock->getFieldLayout()->getFieldByHandle('innerText');

    expect($entry)->not->toBeNull()
        ->and($contentBlock->id)->not->toBeNull()
        ->and($innerLayoutField)->not->toBeNull()
        ->and($contentBlock->getFieldValue('innerText'))->toBe('Nested content block value');
});

it('persists asset field values when creating an entry', function () {
    $asset = AssetModel::factory()->createElement();

    $assetsField = Field::factory()->create([
        'name' => 'Asset Field',
        'handle' => 'assetField',
        'type' => Assets::class,
    ]);

    $fieldLayout = FieldLayout::create([
        'type' => Entry::class,
        'config' => createFieldLayoutConfig($assetsField),
    ]);

    $this->entryType->update(['fieldLayoutId' => $fieldLayout->id]);

    EntryTypes::refreshEntryTypes();
    Fields::invalidateCaches();
    Fields::refreshFields();

    post(action(StoreEntryController::class), [
        'sectionId' => $this->section->id,
        'title' => 'Asset Entry',
        'slug' => 'asset-entry',
        'fields' => [
            'assetField' => [$asset->id],
        ],
    ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $entry = Entry::find()->slug('asset-entry')->status(null)->one();
    $relatedAssetIds = $entry->getFieldValue('assetField')->ids();

    expect($entry)->not->toBeNull()
        ->and($relatedAssetIds)->toBe([$asset->id]);
});
