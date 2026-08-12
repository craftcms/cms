<?php

declare(strict_types=1);

use JMac\Testing\Double;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements as ElementsService;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\DuplicateElementController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Testing\Fluent\AssertableJson;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create([
        'handle' => 'blog',
        'enableVersioning' => true,
    ]);
});

describe('duplicate', function () {
    it('requires authentication', function () {
        Auth::logout();

        postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
        ])->assertUnauthorized();
    });

    it('returns 400 when no element is identified by the request', function () {
        postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
            'elementId' => 999999,
            'siteId' => Sites::getPrimarySite()->id,
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
        /** @var Entry $revision */
        $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
            'revisionId' => $revision->revisionId,
            'siteId' => $revision->siteId,
        ])->assertBadRequest();
    });

    it('forbids duplicating an element without permission', function () {
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

        postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertForbidden();
    });

    it('returns a failure response when duplication raises an invalid element exception', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        $entry->errors()->add('title', 'Title is invalid.');

        $elements = Double::for(ElementsService::class);
        $elements->expects('duplicateElement')->throws(new InvalidElementException($entry));

        app()->instance(ElementsService::class, $elements);

        postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertBadRequest()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('Couldn’t duplicate {type}.', ['type' => Entry::lowerDisplayName()]))
                ->where('errors.title.0', 'Title is invalid.')
                ->etc()
            );
    });

    it('duplicates a canonical element', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);

        $response = postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('{type} duplicated.', ['type' => Entry::displayName()]))
                ->where('modelName', 'element')
                ->where('element.title', 'Canonical Title')
                ->etc()
            );

        /** @var Entry $duplicate */
        $duplicate = Entry::find()
            ->id($response->json('element.id'))
            ->siteId($entry->siteId)
            ->status(null)
            ->one();

        expect($duplicate)->not->toBeNull()
            ->and($duplicate->id)->not->toBe($entry->id)
            ->and($duplicate->getCanonicalId())->toBe($duplicate->id)
            ->and($duplicate->draftId)->toBeNull()
            ->and($duplicate->title)->toBe('Canonical Title');
    });

    it('duplicates a provisional draft as an unpublished draft and deletes the provisional source', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

        $response = postJson(action([DuplicateElementController::class, 'duplicate']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'asUnpublishedDraft' => true,
            'deleteProvisionalDraft' => true,
        ])->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('{type} duplicated.', ['type' => Entry::displayName()]))
                ->where('element.draftName', t('First draft'))
                ->etc()
            );

        /** @var Entry $duplicate */
        $duplicate = Entry::find()
            ->id($response->json('element.id'))
            ->drafts()
            ->status(null)
            ->one();

        expect($duplicate)->not->toBeNull()
            ->and($duplicate->draftId)->not->toBeNull()
            ->and($duplicate->getIsUnpublishedDraft())->toBeTrue()
            ->and($duplicate->draftName)->toBe(t('First draft'))
            ->and(Entry::find()->draftId($draft->draftId)->status(null)->one())->toBeNull();
    });
});

describe('bulkDuplicate', function () {
    it('requires authentication', function () {
        Auth::logout();

        postJson(action([DuplicateElementController::class, 'bulkDuplicate']))->assertUnauthorized();
    });

    it('validates the payload', function () {
        postJson(action([DuplicateElementController::class, 'bulkDuplicate']), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['elements', 'newAttributes']);
    });

    it('skips unidentified elements', function () {
        postJson(action([DuplicateElementController::class, 'bulkDuplicate']), [
            'elements' => [[
                'type' => Entry::class,
                'id' => 999999,
                'siteId' => Sites::getPrimarySite()->id,
            ]],
            'newAttributes' => [
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
            ],
        ])->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', mb_ucfirst(t('{type} duplicated.', ['type' => Entry::displayName()])))
                ->where('newElements', [])
            );
    });

    it('forbids bulk duplication when the user cannot duplicate the element', function () {
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

        postJson(action([DuplicateElementController::class, 'bulkDuplicate']), [
            'elements' => [[
                'type' => Entry::class,
                'id' => $entry->id,
                'siteId' => $entry->siteId,
            ]],
            'newAttributes' => [
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
            ],
        ])->assertForbidden();
    });

    it('does not allow bulk duplicate attributes to target an existing element ID', function () {
        $source = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Source Title',
                'slug' => 'source-title',
            ]);
        $target = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Target Title',
                'slug' => 'target-title',
            ]);

        postJson(action([DuplicateElementController::class, 'bulkDuplicate']), [
            'elements' => [[
                'type' => Entry::class,
                'id' => $source->id,
                'siteId' => $source->siteId,
            ]],
            'newAttributes' => [
                'id' => $target->id,
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
                'title' => 'Injected Title',
            ],
        ])->assertBadRequest();

        $duplicate = Entry::find()
            ->title('Injected Title')
            ->siteId($source->siteId)
            ->status(null)
            ->one();
        $target = Entry::find()
            ->id($target->id)
            ->siteId($target->siteId)
            ->status(null)
            ->one();

        expect($duplicate)->toBeNull()
            ->and($target->title)->toBe('Target Title');
    });

    it('returns a failure response when bulk duplication raises an invalid element exception', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Canonical Title',
                'slug' => 'canonical-title',
            ]);
        $entry->errors()->add('title', 'Title is invalid.');

        $elements = Double::for(ElementsService::class);
        $elements->expects('duplicateElement')->throws(new InvalidElementException($entry));

        app()->instance(ElementsService::class, $elements);

        postJson(action([DuplicateElementController::class, 'bulkDuplicate']), [
            'elements' => [[
                'type' => Entry::class,
                'id' => $entry->id,
                'siteId' => $entry->siteId,
            ]],
            'newAttributes' => [
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
            ],
        ])->assertBadRequest()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('Couldn’t duplicate {type}.', ['type' => Entry::lowerDisplayName()]))
                ->where('errors.title.0', 'Title is invalid.')
                ->etc()
            );
    });

    it('duplicates revisions as regular elements', function () {
        $entry = EntryModel::factory()
            ->forSection($this->section)
            ->forEntryType($this->entryType)
            ->createElement([
                'title' => 'Original Title',
                'slug' => 'original-title',
            ]);
        /** @var Entry $revision */
        $revision = Elements::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        $response = postJson(action([DuplicateElementController::class, 'bulkDuplicate']), [
            'elements' => [[
                'type' => Entry::class,
                'revisionId' => $revision->revisionId,
                'siteId' => $revision->siteId,
            ]],
            'newAttributes' => [
                'sectionId' => $this->section->id,
                'typeId' => $this->entryType->id,
            ],
        ])->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', mb_ucfirst(t('{type} duplicated.', ['type' => Entry::displayName()])))
                ->has('newElements', 1)
                ->where('newElements.0.title', 'Original Title')
            );

        /** @var Entry $duplicate */
        $duplicate = Entry::find()
            ->id($response->json('newElements.0.id'))
            ->siteId($entry->siteId)
            ->status(null)
            ->one();

        expect($duplicate)->not->toBeNull()
            ->and($duplicate->id)->not->toBe($entry->id)
            ->and($duplicate->revisionId)->toBeNull()
            ->and($duplicate->title)->toBe('Original Title');
    });
});
