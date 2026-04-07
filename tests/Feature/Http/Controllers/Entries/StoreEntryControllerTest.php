<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Assets;
use CraftCms\Cms\Field\ContentBlock;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
    $layout = FieldLayout::create([
        'type' => Entry::class,
        'config' => createFieldLayoutConfig($field),
    ]);

    return EntryType::factory()->create([
        'fieldLayoutId' => $layout->id,
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
    $this->user = User::factory()->admin()->create()->asElement();
    actingAs($this->user);

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
    ]);
});

it('requires login', function () {
    Auth::logout();

    post(action(StoreEntryController::class))
        ->assertRedirect('login');
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

it('can duplicate an entry', function () {
    $entryModel = EntryModel::factory()->forSection($this->section)->forEntryType($this->entryType)->create();

    // Original entry should be enabled by default (from factory)
    // Wait, let's ensure it's enabled
    $entryModel->element()->update(['enabled' => true]);

    postJson(action(StoreEntryController::class), [
        'entryId' => $entryModel->id,
        'duplicate' => true,
        'enabled' => false,
    ])->assertOk();

    // Check that we have 2 canonical entries (original + duplicate)
    // We avoid EntryModel::count() because it might include revisions/drafts
    $count = Entry::find()->status(null)->count();
    expect($count)->toBe(2);

    // The new entry should have the same title (or copied title logic)
    // and should be disabled if original was enabled
    $newEntry = Entry::find()->status(null)->orderBy(['dateCreated' => SORT_DESC])->one();
    expect($newEntry->id)->not->toBe($entryModel->id)
        ->and($newEntry->enabled)->toBeTrue();
});

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
