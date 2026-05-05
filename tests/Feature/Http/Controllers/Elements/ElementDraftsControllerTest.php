<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\BeforeSave;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleting;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

function elementDraftsControllerPayload(Entry $entry, array $overrides = []): array
{
    return array_merge([
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'title' => 'Ported Draft Title',
        'slug' => 'ported-draft-title',
        'draftName' => 'Ported Draft',
        'notes' => 'Ported draft notes',
    ], $overrides);
}

beforeEach(function () {
    actingAs(User::findOne());
});

describe('ensure', function () {
    it('returns 400 when ensure does not identify an element', function () {
        postJson(action([ElementDraftsController::class, 'ensure']), [
            'elementType' => Entry::class,
            'siteId' => Sites::getPrimarySite()->id,
        ])->assertBadRequest();
    });

    it('returns 400 for revisions when ensuring drafts', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $revision */
        $revision = ElementsFacade::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        postJson(action([ElementDraftsController::class, 'ensure']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'revisionId' => $revision->revisionId,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('returns the existing draft when ensure identifies one', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Existing Draft');

        postJson(action([ElementDraftsController::class, 'ensure']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ])->assertOk()
            ->assertJsonPath('elementId', $draft->id);
    });

    it('returns the existing provisional draft for the requested element', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

        postJson(action([ElementDraftsController::class, 'ensure']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertOk()
            ->assertJsonPath('elementId', $draft->id);

        expect(
            Entry::find()
                ->drafts()
                ->provisionalDrafts()
                ->draftOf($entry->id)
                ->draftCreator(auth()->id())
                ->status(null)
                ->count()
        )->toBe(1);
    });

    it('returns an existing provisional draft after resolving the canonical element', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

        $request = Mockery::mock(ElementRequest::class);
        $request->shouldReceive('element')
            ->once()
            ->with([], true)
            ->andReturn($entry);
        $request->shouldReceive('user')
            ->once()
            ->andReturn(auth()->user());

        app()->instance('request', Request::create('/actions/elements/ensure-draft', 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]));

        $response = app()->make(ElementDraftsController::class, [
            'request' => $request,
        ])->ensure();

        $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

        expect($response->getStatusCode())->toBe(200)
            ->and($payload['elementId'])->toBe($draft->id)
            ->and(
                Entry::find()
                    ->drafts()
                    ->provisionalDrafts()
                    ->draftOf($entry->id)
                    ->draftCreator(auth()->id())
                    ->status(null)
                    ->count()
            )->toBe(1);
    });

    it('creates a provisional draft when ensuring a canonical element', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        $response = postJson(action([ElementDraftsController::class, 'ensure']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ]);

        $response->assertOk();

        /** @var Entry $draft */
        $draft = Entry::find()
            ->id($response->json('elementId'))
            ->drafts()
            ->provisionalDrafts()
            ->status(null)
            ->one();

        expect($draft)->not->toBeNull()
            ->and($draft->id)->not->toBe($entry->id)
            ->and($draft->getCanonicalId())->toBe($entry->id)
            ->and($draft->isProvisionalDraft)->toBeTrue()
            ->and($draft->draftCreatorId)->toBe(auth()->id());
    });
});

describe('store', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ElementDraftsController::class, 'store']))->assertUnauthorized();
    });

    it('returns 400 when no element is identified by the request', function () {
        postJson(action([ElementDraftsController::class, 'store']), [
            'elementType' => Entry::class,
            'siteId' => Sites::getPrimarySite()->id,
        ])->assertBadRequest();
    });

    it('returns 400 for revisions', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $revision */
        $revision = ElementsFacade::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        postJson(action([ElementDraftsController::class, 'store']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'revisionId' => $revision->revisionId,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('creates a draft from a canonical element and authorizes previewing it', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        $response = postJson(
            action([ElementDraftsController::class, 'store']),
            elementDraftsControllerPayload($entry),
        );

        $response->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('message', t('{type} saved.', ['type' => t('Draft')]))
                ->where('canonicalId', $entry->id)
                ->where('draftName', 'Ported Draft')
                ->where('draftNotes', 'Ported draft notes')
                ->where('creator', auth()->user()->getName())
                ->etc()
            );

        /** @var Entry $draft */
        $draft = Entry::find()
            ->id($response->json('elementId'))
            ->drafts()
            ->status(null)
            ->one();

        expect($draft)->not->toBeNull()
            ->and($draft->getCanonicalId())->toBe($entry->id)
            ->and($draft->title)->toBe('Ported Draft Title')
            ->and($draft->slug)->toBe('ported-draft-title')
            ->and($draft->draftName)->toBe('Ported Draft')
            ->and($draft->draftNotes)->toBe('Ported draft notes')
            ->and(SessionAuth::checkAuthorization("previewDraft:$draft->draftId"))->toBeTrue();

        expect($response->json('draftElementIds'))->toMatchArray([
            (string) $entry->id => $draft->id,
        ])->and($response->json('draftElementUids'))->toMatchArray([
            $entry->uid => $draft->uid,
        ]);
    });

    it('updates an existing draft', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Existing Draft');

        postJson(action([ElementDraftsController::class, 'store']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'title' => 'Updated Draft Title',
            'slug' => 'updated-draft-title',
            'draftName' => 'Renamed Draft',
            'notes' => 'Updated draft notes',
        ])->assertOk()
            ->assertJson(fn (AssertableJson $json) => $json
                ->where('elementId', $draft->id)
                ->where('draftId', $draft->draftId)
                ->where('draftName', 'Renamed Draft')
                ->where('draftNotes', 'Updated draft notes')
                ->etc()
            );

        /** @var Entry $updatedDraft */
        $updatedDraft = Entry::find()
            ->draftId($draft->draftId)
            ->siteId($draft->siteId)
            ->status(null)
            ->one();

        expect($updatedDraft->title)->toBe('Updated Draft Title')
            ->and($updatedDraft->slug)->toBe('updated-draft-title')
            ->and($updatedDraft->draftName)->toBe('Renamed Draft')
            ->and($updatedDraft->draftNotes)->toBe('Updated draft notes');
    });

    it('overwrites an existing provisional draft for the same element and user', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $existingDraft */
        $existingDraft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);
        $existingDraft->title = 'Existing provisional title';
        ElementsFacade::saveElement($existingDraft);

        $response = postJson(
            action([ElementDraftsController::class, 'store']),
            elementDraftsControllerPayload($entry, [
                'provisional' => true,
                'title' => 'Replacement provisional title',
                'slug' => 'replacement-provisional-title',
            ]),
        );

        $response->assertOk();

        /** @var Entry $replacementDraft */
        $replacementDraft = Entry::find()
            ->id($response->json('elementId'))
            ->drafts()
            ->provisionalDrafts()
            ->status(null)
            ->one();

        expect($replacementDraft)->not->toBeNull()
            ->and($replacementDraft->id)->not->toBe($existingDraft->id)
            ->and($replacementDraft->title)->toBe('Replacement provisional title')
            ->and($replacementDraft->isProvisionalDraft)->toBeTrue()
            ->and(Entry::find()->id($existingDraft->id)->drafts()->status(null)->one())->toBeNull()
            ->and(
                Entry::find()
                    ->drafts()
                    ->provisionalDrafts()
                    ->draftOf($entry->id)
                    ->draftCreator(auth()->id())
                    ->status(null)
                    ->count()
            )->toBe(1);
    });

    it('drops provisional status when requested', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

        postJson(action([ElementDraftsController::class, 'store']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'dropProvisional' => true,
            'title' => 'Saved Draft Title',
        ])->assertOk();

        /** @var Entry $savedDraft */
        $savedDraft = Entry::find()
            ->draftId($draft->draftId)
            ->siteId($draft->siteId)
            ->status(null)
            ->one();

        expect($savedDraft->isProvisionalDraft)->toBeFalse()
            ->and($savedDraft->title)->toBe('Saved Draft Title');
    });

    it('includes cp editor payload fields on the control panel action route', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        postJson(
            cp_url('actions/elements/save-draft'),
            elementDraftsControllerPayload($entry, [
                'title' => 'CP Draft Title',
                'slug' => 'cp-draft-title',
                'draftName' => 'CP Draft',
            ]),
        )->assertOk()
            ->assertJsonPath('title', 'CP Draft Title')
            ->assertJsonPath('docTitle', fn (string $docTitle) => str_contains($docTitle, '(CP Draft)'))
            ->assertJsonStructure([
                'previewTargets',
                'previewParamValue',
                'deltaNames',
                'initialDeltaValues',
                'updatedTimestamp',
                'canonicalUpdatedTimestamp',
            ]);
    });

    it('forbids saving a peer draft without save permission', function () {
        $entryType = EntryType::factory()->create();
        $section = Section::factory()->withEntryTypes($entryType)->create();
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->createElement([
                'title' => 'Canonical Title',
            ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Peer Draft');

        $viewer = UserModel::factory()
            ->withPermissions([
                'accessCp',
                sprintf('editSite:%s', Sites::getPrimarySite()->uid),
                sprintf('viewEntries:%s', $section->uid),
                sprintf('viewPeerEntryDrafts:%s', $section->uid),
            ])
            ->createElement();

        actingAs($viewer);

        postJson(action([ElementDraftsController::class, 'store']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'title' => 'Unauthorized Update',
        ])->assertForbidden();
    });

    it('returns any response resolved by the element request', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        post(action([ElementDraftsController::class, 'store']), [
            'elementId' => $entry->id,
            'draftId' => 999999,
            'siteId' => $entry->siteId,
        ])->assertRedirect($entry->getCpEditUrl());
    });

    it('returns a failure response when saving the draft fails', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Failing Draft');

        Event::listen(BeforeSave::class, function (BeforeSave $event) use ($draft) {
            if ($event->element->id === $draft->id) {
                $event->isValid = false;
            }
        });

        postJson(action([ElementDraftsController::class, 'store']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ])->assertStatus(400)
            ->assertJsonPath('message', t('Couldn’t save {type}.', ['type' => t('draft')]));
    });

    it('rechecks save authorization after applying request params', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Guarded Draft');

        $request = ElementRequest::create('/actions/elements/save-draft', 'POST', [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ]);
        $request->setUserResolver(fn () => auth()->user());
        app()->instance('request', $request);

        $controller = new class($request, app(Drafts::class), app(Elements::class), app(ElementActivity::class)) extends ElementDraftsController
        {
            private int $canSaveCalls = 0;

            protected function applyParamsToElement(ElementInterface $element): void {}

            protected function canSave(ElementInterface $element, User $user): bool
            {
                return ++$this->canSaveCalls === 1;
            }
        };

        expect(fn () => $controller->store())
            ->toThrow(HttpException::class, 'User not authorized to save this element.');
    });
});

describe('apply', function () {
    it('requires authentication', function () {
        auth()->logout();

        postJson(action([ElementDraftsController::class, 'apply']))->assertUnauthorized();
    });

    it('returns 400 when no draft is identified by the request', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);

        postJson(action([ElementDraftsController::class, 'apply']), [
            'elementType' => Entry::class,
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('returns any response resolved by the element request', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        post(action([ElementDraftsController::class, 'apply']), [
            'elementId' => $entry->id,
            'draftId' => 999999,
            'siteId' => $entry->siteId,
        ])->assertRedirect($entry->getCpEditUrl());
    });

    it('applies a draft to its canonical element', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Existing Draft');

        postJson(action([ElementDraftsController::class, 'apply']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'title' => 'Applied Draft Title',
            'slug' => 'applied-draft-title',
        ])->assertOk()
            ->assertJsonPath('message', t('Draft applied.'));

        /** @var Entry $canonical */
        $canonical = Entry::find()
            ->id($entry->id)
            ->status(null)
            ->one();

        expect($canonical->title)->toBe('Applied Draft Title')
            ->and($canonical->slug)->toBe('applied-draft-title')
            ->and(Entry::find()->draftId($draft->draftId)->status(null)->one())->toBeNull();
    });

    it('forbids applying a draft when the user cannot save the canonical element', function () {
        $entryType = EntryType::factory()->create();
        $section = Section::factory()->withEntryTypes($entryType)->create();
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->createElement([
                'title' => 'Canonical Title',
            ]);

        $viewer = UserModel::factory()
            ->withPermissions([
                'accessCp',
                sprintf('editSite:%s', Sites::getPrimarySite()->uid),
                sprintf('viewEntries:%s', $section->uid),
            ])
            ->createElement();

        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, $viewer->id, name: 'Viewer Draft');

        actingAs($viewer);

        postJson(action([ElementDraftsController::class, 'apply']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'title' => 'Unauthorized Apply',
        ])->assertForbidden();
    });

    it('returns a failure response and preserves the draft when applying fails validation', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Failing Draft');

        $failedSave = false;

        Event::listen(BeforeSave::class, function (BeforeSave $event) use ($draft, &$failedSave) {
            if ($event->element->id === $draft->id && ! $failedSave) {
                $event->isValid = false;
                $failedSave = true;
            }
        });

        postJson(action([ElementDraftsController::class, 'apply']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'title' => 'Failed Apply Title',
            'slug' => 'failed-apply-title',
        ])->assertBadRequest()
            ->assertJsonPath('message', t('Couldn’t apply draft.'));

        /** @var Entry $savedDraft */
        $savedDraft = Entry::find()
            ->draftId($draft->draftId)
            ->siteId($draft->siteId)
            ->status(null)
            ->one();

        /** @var Entry $canonical */
        $canonical = Entry::find()
            ->id($entry->id)
            ->status(null)
            ->one();

        expect($savedDraft)->not->toBeNull()
            ->and($savedDraft->title)->toBe('Failed Apply Title')
            ->and($savedDraft->slug)->toBe('failed-apply-title')
            ->and($canonical->title)->toBe('Canonical Title')
            ->and($canonical->slug)->toBe('canonical-title');
    });
});

describe('destroy', function () {
    it('returns any response resolved by the element request', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        post(action([ElementDraftsController::class, 'destroy']), [
            'elementId' => $entry->id,
            'draftId' => 999999,
            'siteId' => $entry->siteId,
        ])->assertRedirect($entry->getCpEditUrl());
    });

    it('returns 400 when no draft is identified by the request', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);

        postJson(action([ElementDraftsController::class, 'destroy']), [
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('deletes a draft', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Disposable Draft');

        $response = postJson(action([ElementDraftsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', t('{type} deleted.', ['type' => t('Draft')]));

        expect(Entry::find()->draftId($draft->draftId)->status(null)->one())->toBeNull();
    });

    it('discards provisional draft changes', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

        $response = postJson(action([ElementDraftsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ]);

        $response->assertOk()
            ->assertJsonPath('message', t('Changes discarded.'));

        expect(
            Entry::find()
                ->drafts()
                ->provisionalDrafts()
                ->draftOf($entry->id)
                ->draftCreator(auth()->id())
                ->status(null)
                ->one()
        )->toBeNull();
    });

    it('returns a failure response when deleting the draft fails', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
        ]);
        /** @var Entry $draft */
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Undeletable Draft');

        Event::listen(ElementLifecycleDeleting::class, function (ElementLifecycleDeleting $event) use ($draft) {
            if ($event->element->id === $draft->id) {
                $event->isValid = false;
            }
        });

        postJson(action([ElementDraftsController::class, 'destroy']), [
            'elementType' => Entry::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ])->assertStatus(400)
            ->assertJsonPath('message', t('Couldn’t delete {type}.', ['type' => t('draft')]));

        expect(Entry::find()->draftId($draft->draftId)->status(null)->one())->not->toBeNull();
    });
});
