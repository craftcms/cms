<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\SessionAuth;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\ElementActivity;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\BeforeSave;
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
use Illuminate\Support\Facades\Event;
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
        ->assertJsonPath('message', t('{type} saved.', ['type' => t('Draft')]))
        ->assertJsonPath('canonicalId', $entry->id)
        ->assertJsonPath('draftName', 'Ported Draft')
        ->assertJsonPath('draftNotes', 'Ported draft notes')
        ->assertJsonPath('creator', auth()->user()->getName());

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
        ->assertJsonPath('elementId', $draft->id)
        ->assertJsonPath('draftId', $draft->draftId)
        ->assertJsonPath('draftName', 'Renamed Draft')
        ->assertJsonPath('draftNotes', 'Updated draft notes');

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

    $request = ElementRequest::create('/actions/elements/save-draft', 'POST', [
        'elementType' => Entry::class,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
    ], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]);
    $request->setUserResolver(fn () => auth()->user());
    app()->instance('request', $request);

    Event::listen(BeforeSave::class, function (BeforeSave $event) use ($draft) {
        if ($event->element->id === $draft->id) {
            $event->isValid = false;
        }
    });

    $controller = new class($request, app(Drafts::class), app(Elements::class), app(ElementActivity::class)) extends ElementDraftsController
    {
        protected function applyParamsToElement(ElementInterface $element): void {}

        protected function canSave(ElementInterface $element, User $user): bool
        {
            return true;
        }
    };

    $response = $controller->store();

    $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);

    expect($response->getStatusCode())->toBe(400)
        ->and($payload['message'])->toBe(t('Couldn’t save {type}.', ['type' => t('draft')]));
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
