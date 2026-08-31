<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume;
use CraftCms\Cms\Asset\Models\VolumeFolder;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Contracts\NestedElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements as ElementsService;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Enums\ElementActivityType;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Operations\ElementPlaceholders;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Http\Controllers\Elements\SaveElementController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes as EntryTypesFacade;
use CraftCms\Cms\Support\Facades\Fields as FieldsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'news',
        'enableVersioning' => true,
    ]);
});

/** A saved, disabled entry. `enabled` lives on `elements`, so not a factory attribute. */
function disabledEntry(Section $section, EntryType $entryType): Entry
{
    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

    /** @var Entry $element */
    $element = Entry::find()->id($entry->id)->status(null)->one();
    $element->enabled = false;
    Elements::saveElement($element);

    return $element;
}

function createSaveElementMatrixFixture(): array
{
    $innerField = Field::factory()->create([
        'name' => 'Inner Text',
        'handle' => 'innerText',
        'type' => PlainText::class,
    ]);

    $matrixEntryType = EntryType::factory()
        ->withField($innerField)
        ->create([
            'name' => 'Matrix Block',
            'handle' => 'matrixBlock',
            'hasTitleField' => true,
        ]);

    $matrixField = Field::factory()->create([
        'name' => 'Matrix Field',
        'handle' => 'matrixField',
        'type' => Matrix::class,
        'settings' => ['entryTypes' => [$matrixEntryType->id]],
    ]);

    $ownerType = EntryType::factory()
        ->withField($matrixField)
        ->create([
            'name' => 'Owner',
            'handle' => 'owner',
            'hasTitleField' => true,
        ]);

    $section = Section::factory()
        ->withEntryTypes($ownerType)
        ->create([
            'handle' => 'owners',
        ]);

    $owner = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($ownerType)
        ->createElement([
            'title' => 'Owner Entry',
            'slug' => Str::slug('Owner Entry '.Str::random(6)),
        ]);

    EntryTypesFacade::refreshEntryTypes();
    FieldsFacade::invalidateCaches();

    $matrixField = FieldsFacade::getFieldById($matrixField->id);
    /** @var Entry $owner */
    $owner = Entry::find()->id($owner->id)->status(null)->one();

    $blockUid = Str::uuid()->toString();
    $owner->setFieldValueFromRequest('matrixField', [
        'entries' => [
            "uid:$blockUid" => [
                'type' => $matrixEntryType->handle,
                'title' => 'Block 1',
                'enabled' => true,
                'fields' => [
                    'innerText' => 'Canonical matrix value',
                ],
            ],
        ],
        'sortOrder' => [$blockUid],
    ]);

    expect(Elements::saveElement($owner))->toBeTrue();

    $owner = Entry::find()->id($owner->id)->status(null)->one();
    $canonicalBlock = $owner->getFieldValue('matrixField')->status(null)->one();
    $ownerDraft = app(Drafts::class)->createDraft($owner, auth()->id(), name: 'Owner Draft');
    $draftBlock = app(Drafts::class)->createDraft($canonicalBlock, auth()->id(), name: 'Block Draft');

    /** @var Entry $ownerDraft */
    $ownerDraft = Entry::find()
        ->draftId($ownerDraft->draftId)
        ->siteId($ownerDraft->siteId)
        ->status(null)
        ->one();
    $draftBlock = Entry::find()
        ->draftId($draftBlock->draftId)
        ->fieldId($matrixField->id)
        ->ownerId($owner->id)
        ->drafts()
        ->siteId($draftBlock->siteId)
        ->status(null)
        ->one();

    return [
        'field' => $matrixField,
        'owner' => $owner,
        'ownerDraft' => $ownerDraft,
        'canonicalBlock' => $canonicalBlock,
        'draftBlock' => $draftBlock,
    ];
}

describe('store', function () {
    it('requires authentication', function () {
        Auth::logout();

        postJson(action([SaveElementController::class, 'store']))->assertUnauthorized();
    });

    it('returns any response resolved by the element request', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        post(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'draftId' => 999999,
        ])->assertRedirect($entry->getCpEditUrl());
    });

    it('returns 400 when no element is identified by the request', function () {
        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'siteId' => Sites::getPrimarySite()->id,
        ])->assertBadRequest();
    });

    it('returns 400 for drafts', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Existing Draft');

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ])->assertBadRequest();
    });

    it('returns 400 for revisions', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        $revisionId = app(Revisions::class)->createRevision($entry, auth()->id());

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'revisionId' => $revisionId,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('ignores sensitive attributes when saving a user element', function () {
        $editor = UserModel::factory()->admin()->createElement();
        $targetUser = UserModel::factory()->createElement();
        $originalPassword = DB::table(Table::USERS)
            ->where('id', $targetUser->id)
            ->value('password');
        $originalEmail = $targetUser->email;

        actingAs($editor);

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => User::class,
            'elementId' => $targetUser->id,
            'email' => 'updated@example.com',
            'firstName' => 'Updated',
            'newPassword' => 'SecurePassword123!',
        ])->assertOk();

        $userRecord = DB::table(Table::USERS)
            ->where('id', $targetUser->id)
            ->first();

        expect($userRecord->password)->toBe($originalPassword)
            ->and($userRecord->email)->toBe($originalEmail)
            ->and($userRecord->firstName)->toBe('Updated');
    });

    it('forbids saving when the user cannot save the element', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        $viewer = UserModel::factory()
            ->withPermissions([
                'accessCp',
                sprintf('editSite:%s', Sites::getPrimarySite()->uid),
                sprintf('viewEntries:%s', $this->section->uid),
                sprintf('viewPeerEntries:%s', $this->section->uid),
            ])
            ->createElement();

        actingAs($viewer);

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Updated Title',
        ])->assertForbidden();
    });

    it('returns 500 when it cannot acquire a lock for an existing element', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        $lock = Cache::lock("element:$entry->id", 15);

        expect($lock->get())->toBeTrue();

        try {
            postJson(action([SaveElementController::class, 'store']), [
                'elementType' => Entry::class,
                'elementId' => $entry->id,
                'siteId' => $entry->siteId,
                'title' => 'Updated Title',
            ])->assertInternalServerError();
        } finally {
            $lock->release();
        }
    });

    it('returns a failure response when saving fails', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        $entry->errors()->add('title', 'Title is invalid.');

        app()->instance(ElementsService::class, new class(app(ElementPlaceholders::class), app(ElementTypes::class)) extends ElementsService
        {
            public function saveElement(
                ElementInterface $element,
                bool $runValidation = true,
                bool $propagate = true,
                ?bool $updateSearchIndex = null,
                bool $forceTouch = false,
                ?bool $crossSiteValidate = false,
                bool $saveContent = false,
            ): bool {
                return false;
            }
        });

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Updated Title',
        ])->assertBadRequest()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => Entry::lowerDisplayName(),
                ])))
                ->where('modelName', 'element')
                ->etc()
            );
    });

    it('returns a failure response when saving throws an unsupported site exception', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        app()->instance(ElementsService::class, new class(app(ElementPlaceholders::class), app(ElementTypes::class)) extends ElementsService
        {
            public function saveElement(
                ElementInterface $element,
                bool $runValidation = true,
                bool $propagate = true,
                ?bool $updateSearchIndex = null,
                bool $forceTouch = false,
                ?bool $crossSiteValidate = false,
                bool $saveContent = false,
            ): bool {
                throw new UnsupportedSiteException($element, 999999, 'Unsupported site.');
            }
        });

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Updated Title',
        ])->assertBadRequest()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('errors.siteId.0', 'Unsupported site.')
                ->etc()
            );
    });

    it('saves canonical elements, tracks save activity, deletes provisional drafts, and cross-site validates for multisite requests', function () {
        Site::factory()->create(['handle' => 'secondary']);

        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
        actingAs(UserModel::findOrFail(auth()->id()));

        $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class)) extends ElementsService
        {
            public ?bool $capturedCrossSiteValidate = null;

            public function saveElement(
                ElementInterface $element,
                bool $runValidation = true,
                bool $propagate = true,
                ?bool $updateSearchIndex = null,
                bool $forceTouch = false,
                ?bool $crossSiteValidate = false,
                bool $saveContent = false,
            ): bool {
                $this->capturedCrossSiteValidate = $crossSiteValidate;

                return parent::saveElement(
                    $element,
                    $runValidation,
                    $propagate,
                    $updateSearchIndex,
                    $forceTouch,
                    $crossSiteValidate,
                    $saveContent,
                );
            }
        };

        app()->instance(ElementsService::class, $elements);

        $response = postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Updated Title',
            'slug' => 'updated-title',
        ]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('{type} saved.', ['type' => Entry::displayName()]))
                ->where('modelName', 'element')
                ->where('element.id', $entry->id)
                ->where('element.title', 'Updated Title')
                ->where('element.slug', 'updated-title')
                ->etc()
            );

        expect($elements->capturedCrossSiteValidate)->toBeTrue()
            ->and(Entry::find()->id($entry->id)->status(null)->one()->title)->toBe('Updated Title')
            ->and(
                Entry::find()
                    ->drafts()
                    ->provisionalDrafts()
                    ->draftOf($entry->id)
                    ->draftCreator(auth()->id())
                    ->status(null)
                    ->count()
            )->toBe(0)
            ->and(DB::table(Table::ELEMENTACTIVITY)
                ->where('elementId', $entry->id)
                ->where('userId', auth()->id())
                ->where('type', ElementActivityType::Save->value)
                ->exists())
            ->toBeTrue();
    });

    /**
     * One save per test on purpose: the controller takes its `ElementRequest`
     * through the constructor, so a second post in the same process is served
     * the first one's params.
     */
    it('disables an element when the status control posts false', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        expect($entry->enabled)->toBeTrue();

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'enabled' => false,
        ])->assertOk();

        expect(Entry::find()->id($entry->id)->status(null)->one()->enabled)->toBeFalse();
    });

    it('enables a disabled element when the status control posts true', function () {
        $entry = disabledEntry($this->section, $this->entryType);

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'enabled' => true,
        ])->assertOk();

        expect(Entry::find()->id($entry->id)->status(null)->one()->enabled)->toBeTrue();
    });

    /**
     * Unlike Craft 5, which force-enabled on a POST carrying no status: saves
     * that don't carry a status control — a delta save of one field, say —
     * leave the element's status alone. See CHANGELOG-WIP.md.
     */
    it('leaves the status alone when none is posted', function () {
        $entry = disabledEntry($this->section, $this->entryType);

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Renamed',
        ])->assertOk();

        $saved = Entry::find()->id($entry->id)->status(null)->one();

        expect($saved->title)->toBe('Renamed')
            ->and($saved->enabled)->toBeFalse();
    });

    it('can clear asset alt text', function () {
        Queue::fake();
        config()->set('filesystems.disks.save-element-controller-test', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/save-element-controller-test'),
        ]);

        $volume = Volume::factory()->create(['fs' => 'disk:save-element-controller-test']);
        $folder = VolumeFolder::factory()->create(['volumeId' => $volume->id]);
        $asset = AssetModel::factory()->createElement([
            'volumeId' => $volume->id,
            'folderId' => $folder->id,
            'alt' => 'Existing alt text',
        ]);

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Asset::class,
            'elementId' => $asset->id,
            'siteId' => $asset->siteId,
            'alt' => '',
        ])->assertOk();

        expect(Asset::find()->id($asset->id)->one()->alt)->toBe('');
    });

    it('marks nested elements to update their owner search index before saving', function () {
        $fixture = createSaveElementMatrixFixture();

        $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class)) extends ElementsService
        {
            public bool $capturedNestedOwnerIndexFlag = false;

            public function saveElement(
                ElementInterface $element,
                bool $runValidation = true,
                bool $propagate = true,
                ?bool $updateSearchIndex = null,
                bool $forceTouch = false,
                ?bool $crossSiteValidate = false,
                bool $saveContent = false,
            ): bool {
                if ($element instanceof NestedElementInterface) {
                    $this->capturedNestedOwnerIndexFlag = $element->updateSearchIndexForOwner;
                }

                return parent::saveElement(
                    $element,
                    $runValidation,
                    $propagate,
                    $updateSearchIndex,
                    $forceTouch,
                    $crossSiteValidate,
                    $saveContent,
                );
            }
        };

        app()->instance(ElementsService::class, $elements);

        postJson(action([SaveElementController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['canonicalBlock']->id,
            'siteId' => $fixture['canonicalBlock']->siteId,
            'ownerId' => $fixture['owner']->id,
            'fieldId' => $fixture['field']->id,
            'title' => 'Updated Block Title',
        ])->assertOk();

        expect($elements->capturedNestedOwnerIndexFlag)->toBeTrue();
    });

    it('redirects to a new draft when add another is requested', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        $draftCount = Entry::find()->drafts()->status(null)->count();

        post(cp_url('actions/elements/save'), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
            'title' => 'Updated Title',
            'addAnother' => 1,
        ])->assertRedirect();

        expect(Entry::find()->drafts()->status(null)->count())->toBe($draftCount + 1);
    });
});

describe('storeForDerivative', function () {
    it('requires authentication', function () {
        Auth::logout();

        postJson(action([SaveElementController::class, 'storeForDerivative']))->assertUnauthorized();
    });

    it('returns 400 when no new owner is identified', function () {
        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
        ])->assertBadRequest();
    });

    it('returns 400 when the element is not a nested draft derivative', function () {
        $fixture = createSaveElementMatrixFixture();

        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['canonicalBlock']->id,
            'siteId' => $fixture['canonicalBlock']->siteId,
            'ownerId' => $fixture['owner']->id,
            'fieldId' => $fixture['field']->id,
            'newOwnerId' => $fixture['ownerDraft']->id,
        ])->assertBadRequest();
    });

    it('returns 400 when the new owner is canonical', function () {
        $fixture = createSaveElementMatrixFixture();

        DB::table(Table::ENTRIES)
            ->where('id', $fixture['draftBlock']->id)
            ->update(['primaryOwnerId' => $fixture['owner']->id]);

        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['draftBlock']->id,
            'siteId' => $fixture['draftBlock']->siteId,
            'ownerId' => $fixture['ownerDraft']->id,
            'fieldId' => $fixture['field']->id,
            'draftId' => $fixture['draftBlock']->draftId,
            'newOwnerId' => $fixture['owner']->id,
        ])->assertBadRequest();
    });

    it('returns 400 when the new owner does not share the nested element primary owner', function () {
        $fixture = createSaveElementMatrixFixture();

        DB::table(Table::ENTRIES)
            ->where('id', $fixture['draftBlock']->id)
            ->update(['primaryOwnerId' => $fixture['owner']->id]);

        $otherFixture = createSaveElementMatrixFixture();

        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['draftBlock']->id,
            'siteId' => $fixture['draftBlock']->siteId,
            'ownerId' => $fixture['ownerDraft']->id,
            'fieldId' => $fixture['field']->id,
            'draftId' => $fixture['draftBlock']->draftId,
            'newOwnerId' => $otherFixture['ownerDraft']->id,
        ])->assertBadRequest();
    });

    it('forbids saving when the derivative owner cannot be saved', function () {
        $fixture = createSaveElementMatrixFixture();

        DB::table(Table::ENTRIES)
            ->where('id', $fixture['draftBlock']->id)
            ->update(['primaryOwnerId' => $fixture['owner']->id]);

        Gate::before(function ($user, string $ability, array $arguments) use ($fixture) {
            if ($ability === 'save' && ($arguments[0]->id ?? null) === $fixture['ownerDraft']->id) {
                return false;
            }

            return null;
        });

        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['draftBlock']->id,
            'siteId' => $fixture['draftBlock']->siteId,
            'ownerId' => $fixture['ownerDraft']->id,
            'fieldId' => $fixture['field']->id,
            'draftId' => $fixture['draftBlock']->draftId,
            'newOwnerId' => $fixture['ownerDraft']->id,
        ])->assertForbidden();
    });

    it('returns a failure response when saving the derivative fails', function () {
        $fixture = createSaveElementMatrixFixture();

        DB::table(Table::ENTRIES)
            ->where('id', $fixture['draftBlock']->id)
            ->update(['primaryOwnerId' => $fixture['owner']->id]);

        $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class)) extends ElementsService
        {
            public int $saveCalls = 0;

            public function saveElement(
                ElementInterface $element,
                bool $runValidation = true,
                bool $propagate = true,
                ?bool $updateSearchIndex = null,
                bool $forceTouch = false,
                ?bool $crossSiteValidate = false,
                bool $saveContent = false,
            ): bool {
                $this->saveCalls++;

                if ($this->saveCalls === 1) {
                    return true;
                }

                $element->errors()->add('title', 'Title is invalid.');

                return false;
            }
        };

        app()->instance(ElementsService::class, $elements);

        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['draftBlock']->id,
            'siteId' => $fixture['draftBlock']->siteId,
            'ownerId' => $fixture['ownerDraft']->id,
            'fieldId' => $fixture['field']->id,
            'draftId' => $fixture['draftBlock']->draftId,
            'newOwnerId' => $fixture['ownerDraft']->id,
            'title' => 'Updated Block Title',
        ])->assertBadRequest()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', mb_ucfirst(t('Couldn’t save {type}.', [
                    'type' => Entry::lowerDisplayName(),
                ])))
                ->etc()
            );
    });

    it('returns a failure response when saving the derivative throws an unsupported site exception', function () {
        $fixture = createSaveElementMatrixFixture();

        DB::table(Table::ENTRIES)
            ->where('id', $fixture['draftBlock']->id)
            ->update(['primaryOwnerId' => $fixture['owner']->id]);

        $elements = new class(app(ElementPlaceholders::class), app(ElementTypes::class)) extends ElementsService
        {
            public int $saveCalls = 0;

            public function saveElement(
                ElementInterface $element,
                bool $runValidation = true,
                bool $propagate = true,
                ?bool $updateSearchIndex = null,
                bool $forceTouch = false,
                ?bool $crossSiteValidate = false,
                bool $saveContent = false,
            ): bool {
                $this->saveCalls++;

                if ($this->saveCalls === 1) {
                    return true;
                }

                throw new UnsupportedSiteException($element, 999999, 'Unsupported site.');
            }
        };

        app()->instance(ElementsService::class, $elements);

        postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['draftBlock']->id,
            'siteId' => $fixture['draftBlock']->siteId,
            'ownerId' => $fixture['ownerDraft']->id,
            'fieldId' => $fixture['field']->id,
            'draftId' => $fixture['draftBlock']->draftId,
            'newOwnerId' => $fixture['ownerDraft']->id,
            'title' => 'Updated Block Title',
        ])->assertBadRequest()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('errors.siteId.0', 'Unsupported site.')
                ->etc()
            );
    });

    it('saves nested draft elements for derivative owners and removes their draft data', function () {
        $fixture = createSaveElementMatrixFixture();

        DB::table(Table::ENTRIES)
            ->where('id', $fixture['draftBlock']->id)
            ->update(['primaryOwnerId' => $fixture['owner']->id]);

        $sortOrder = DB::table(Table::ELEMENTS_OWNERS)
            ->where('elementId', $fixture['draftBlock']->id)
            ->where('ownerId', $fixture['owner']->id)
            ->value('sortOrder');

        $draftRowExists = DB::table(Table::DRAFTS)
            ->where('id', $fixture['draftBlock']->draftId)
            ->exists();

        expect($draftRowExists)->toBeTrue();

        $response = postJson(action([SaveElementController::class, 'storeForDerivative']), [
            'elementType' => Entry::class,
            'elementId' => $fixture['draftBlock']->id,
            'siteId' => $fixture['draftBlock']->siteId,
            'ownerId' => $fixture['ownerDraft']->id,
            'fieldId' => $fixture['field']->id,
            'draftId' => $fixture['draftBlock']->draftId,
            'newOwnerId' => $fixture['ownerDraft']->id,
            'title' => 'Updated Block Title',
        ]);

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('{type} saved.', ['type' => Entry::displayName()]))
                ->where('modelName', 'element')
                ->where('element.id', $fixture['draftBlock']->id)
                ->where('element.title', 'Updated Block Title')
                ->etc()
            );

        /** @var Entry $savedBlock */
        $savedBlock = Entry::find()
            ->id($fixture['draftBlock']->id)
            ->status(null)
            ->one();

        expect($savedBlock->draftId)->toBeNull()
            ->and($savedBlock->getOwnerId())->toBe($fixture['ownerDraft']->id)
            ->and($savedBlock->getPrimaryOwnerId())->toBe($fixture['ownerDraft']->id)
            ->and($savedBlock->title)->toBe('Updated Block Title')
            ->and(DB::table(Table::ELEMENTS_OWNERS)
                ->where('elementId', $savedBlock->id)
                ->where('ownerId', $fixture['ownerDraft']->id)
                ->value('sortOrder'))
            ->toBe($sortOrder)
            ->and(DB::table(Table::DRAFTS)
                ->where('id', $fixture['draftBlock']->draftId)
                ->exists())
            ->toBeFalse();
    });
});
