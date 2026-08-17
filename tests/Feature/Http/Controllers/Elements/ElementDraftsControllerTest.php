<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleting;
use CraftCms\Cms\Element\Events\ElementLifecycleSaving;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Requests\ElementRequest;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\Fluent\AssertableJson;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
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
        $request->shouldReceive('craftUser')
            ->once()
            ->andReturn(currentUser());

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
                ->where('creator', currentUser()->asElement()->getName())
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

    // Autosave turns a canonical element into a provisional draft midway through
    // editing. Without the screen payload the client only learns about the draft
    // id, so the “Showing your unsaved changes” notice, the Discard changes
    // button and the rest of the draft chrome don’t appear until a page load.
    it('returns the edit screen payload a fresh page load would render', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        $response = postJson(
            cp_url('actions/elements/save-draft'),
            elementDraftsControllerPayload($entry, [
                'provisional' => true,
                'title' => 'Provisional Title',
                'slug' => 'provisional-title',
            ]),
        )->assertOk();

        expect($response->json('screen.notice'))->toBe(t('Showing your unsaved changes.'))
            ->and($response->json('screen.canDiscardDraft'))->toBeTrue()
            ->and($response->json('screen.isProvisionalDraft'))->toBeTrue()
            ->and($response->json('screen.draftId'))->toBe($response->json('draftId'))
            ->and($response->json('screen.elementId'))->toBe($response->json('elementId'))
            ->and($response->json('screen.canonicalId'))->toBe($entry->id)
            // Saving now means applying the draft, not saving the element under it.
            ->and($response->json('screen.applyDraftUrl'))->toContain('elements/apply-draft')
            ->and($response->json('screen.submitButtonLabel'))->toBe(t('Save'));

        // The rest of the chrome the initial load carries, which the screen has
        // no other way to refresh mid-edit.
        $response->assertJsonStructure([
            'screen' => [
                'sidebarForm',
                'metadataHtml',
                'statusLabelHtml',
                'crumbs',
                'formActions',
                'headerActions',
                'actionMenu',
                'previewTargets',
                'updatedTimestamps',
                'mergeNotice',
                'canAutosave',
                'readOnly',
                'title',
                'docTitle',
            ],
        ]);

        // The compiled layout is already on the response, scoped to whatever the
        // request asked for — sending it a second time would double the size of
        // every keystroke’s autosave.
        expect($response->json('screen'))->not->toHaveKey('form')
            ->and($response->json('form'))->toBeArray();
    });

    // The legacy `Craft.ElementEditor` reads this response too, and several of
    // its keys mean something different there — `form` is scoped to the editor’s
    // namespace, `previewTargets` is the raw target list. The screen payload is
    // nested for that reason; this guards the keys it must not have disturbed.
    it('keeps every key the legacy element editor reads', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        $response = postJson(
            cp_url('actions/elements/save-draft'),
            elementDraftsControllerPayload($entry, [
                'title' => 'Legacy Draft Title',
                'slug' => 'legacy-draft-title',
            ]),
        )->assertOk();

        $response->assertJsonStructure([
            'elementId',
            'draftId',
            'draftName',
            'creator',
            'timestamp',
            'modifiedAttributes',
            'draftElementIds',
            'draftElementUids',
            'deltaNames',
            'initialDeltaValues',
            'form',
            'tabs',
            'headHtml',
            'bodyHtml',
            'docTitle',
            'title',
            'previewTargets',
            'previewParamValue',
            'updatedTimestamp',
            'canonicalUpdatedTimestamp',
        ]);

        // `_afterUpdateFieldLayout()` throws on a falsy `form`, and
        // `modifiedAttributes` is mapped over unguarded.
        expect($response->json('form'))->toBeArray()
            ->and($response->json('modifiedAttributes'))->toBeArray()
            // The raw target list, not the screen payload’s resolved links.
            ->and($response->json('previewTargets'))->toBeArray();
    });

    it('reports the same updated timestamp that recent activity reads back', function () {
        // The two only disagree when the system timezone isn’t UTC: the element carries the
        // `dateUpdated` it was saved with, while recent activity re-reads it from the database.
        Cms::config()->timezone('America/Chicago');
        Cms::setDefaultTimezone();

        expect(date_default_timezone_get())->toBe('America/Chicago');

        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Title',
            'slug' => 'canonical-title',
        ]);

        $response = postJson(
            cp_url('actions/elements/save-draft'),
            elementDraftsControllerPayload($entry, [
                'title' => 'Timestamped Draft Title',
                'slug' => 'timestamped-draft-title',
            ]),
        )->assertOk();

        // Only one request per test — the controllers constructor-inject ElementRequest, so a
        // second POST in this process would resolve against the first request’s params. This is
        // the same read elements/recent-activity performs to report its `updatedTimestamp`.
        /** @var Entry $draft */
        $draft = Entry::find()
            ->id($response->json('elementId'))
            ->drafts()
            ->status(null)
            ->one();

        expect($response->json('updatedTimestamp'))->toBe($draft->dateUpdated->getTimestamp())
            // …and both describe the moment the draft was actually saved, rather than agreeing on
            // an instant that’s a timezone offset away from it.
            ->and(abs($response->json('updatedTimestamp') - now()->getTimestamp()))->toBeLessThan(30);
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

        Event::listen(ElementLifecycleSaving::class, function (ElementLifecycleSaving $event) use ($draft) {
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
        $request->setUserResolver(fn () => currentUser());
        app()->instance('request', $request);

        $controller = new class($request, app(Drafts::class), app(Elements::class), app(ElementActivity::class)) extends ElementDraftsController
        {
            private int $canSaveCalls = 0;

            protected function applyParamsToElement(ElementInterface $element): void {}

            protected function canSave(ElementInterface $element, CraftUser $user): bool
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

    it('applies email changes from unpublished user drafts', function () {
        $admin = UserModel::factory()->admin()->createElement();

        actingAs($admin);

        $draft = app(User::class);
        $draft->username = 'new-user';

        expect(app(Drafts::class)->saveElementAsDraft($draft, $admin->id, markAsSaved: false))->toBeTrue();

        postJson(action([ElementDraftsController::class, 'apply']), [
            'elementType' => User::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'email' => 'new-user@example.com',
            'username' => 'new-user',
        ])->assertOk()
            ->assertJsonPath('message', t('{type} created.', [
                'type' => User::displayName(),
            ]));

        expect(User::find()
            ->email('new-user@example.com')
            ->status(null)
            ->one()
        )->not->toBeNull();
    });

    it('ignores other sensitive attributes when applying user drafts', function () {
        $admin = UserModel::factory()->admin()->createElement();
        $targetUser = UserModel::factory()->createElement([
            'active' => false,
            'admin' => false,
            'passwordResetRequired' => false,
        ]);
        $originalPassword = DB::table(Table::USERS)
            ->where('id', $targetUser->id)
            ->value('password');
        /** @var User $draft */
        $draft = app(Drafts::class)->createDraft($targetUser, $admin->id, name: 'User Draft');

        actingAs($admin);

        postJson(action([ElementDraftsController::class, 'apply']), [
            'elementType' => User::class,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
            'firstName' => 'Updated',
            'active' => true,
            'admin' => true,
            'affiliatedSiteId' => 999,
            'currentPassword' => 'password',
            'invalidLoginCount' => 10,
            'lastInvalidLoginDate' => now()->toDateTimeString(),
            'lastLoginAttemptIp' => '127.0.0.1',
            'lastLoginDate' => now()->toDateTimeString(),
            'lastPasswordChangeDate' => now()->toDateTimeString(),
            'locked' => true,
            'lockoutDate' => now()->toDateTimeString(),
            'newPassword' => 'SecurePassword123!',
            'password' => 'password',
            'passwordResetRequired' => true,
            'pending' => true,
            'photoId' => 999,
            'suspended' => true,
            'unverifiedEmail' => 'unverified@example.com',
        ])->assertOk();

        /** @var User $updatedUser */
        $updatedUser = User::find()
            ->id($targetUser->id)
            ->status(null)
            ->one();

        expect($updatedUser->firstName)->toBe('Updated')
            ->and($updatedUser->active)->toBeFalse()
            ->and($updatedUser->admin)->toBeFalse()
            ->and($updatedUser->affiliatedSiteId)->toBeNull()
            ->and($updatedUser->invalidLoginCount)->toBeNull()
            ->and($updatedUser->lastInvalidLoginDate)->toBeNull()
            ->and($updatedUser->lastLoginAttemptIp)->toBeNull()
            ->and($updatedUser->lastLoginDate)->toBeNull()
            ->and($updatedUser->lastPasswordChangeDate)->toBeNull()
            ->and($updatedUser->locked)->toBeFalse()
            ->and($updatedUser->lockoutDate)->toBeNull()
            ->and(DB::table(Table::USERS)->where('id', $targetUser->id)->value('password'))->toBe($originalPassword)
            ->and($updatedUser->passwordResetRequired)->toBeFalse()
            ->and($updatedUser->pending)->toBeFalse()
            ->and($updatedUser->photoId)->toBeNull()
            ->and($updatedUser->suspended)->toBeFalse()
            ->and($updatedUser->unverifiedEmail)->toBeNull();
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

        Event::listen(ElementLifecycleSaving::class, function (ElementLifecycleSaving $event) use ($draft, &$failedSave) {
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
