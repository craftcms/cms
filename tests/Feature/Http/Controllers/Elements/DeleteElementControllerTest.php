<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\ElementLifecycleDeleting;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\DeleteElementController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements as ElementsFacade;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function CraftCms\Cms\t;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());
});

it('requires authentication for delete routes', function () {
    auth()->logout();

    postJson(action([DeleteElementController::class, 'destroy']))->assertUnauthorized();
    postJson(action([DeleteElementController::class, 'destroyForSite']))->assertUnauthorized();
});

describe('destroy', function () {
    it('returns 400 when no element is identified by the request', function () {
        postJson(action([DeleteElementController::class, 'destroy']), [
            'elementType' => Entry::class,
            'siteId' => Sites::getPrimarySite()->id,
        ])->assertBadRequest();
    });

    it('returns 400 for saved drafts', function () {
        $entry = EntryModel::factory()->createElement();
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), name: 'Delete Draft');

        postJson(action([DeleteElementController::class, 'destroy']), [
            'elementId' => $entry->id,
            'draftId' => $draft->draftId,
            'siteId' => $draft->siteId,
        ])->assertBadRequest();
    });

    it('returns 400 for revisions', function () {
        $entry = EntryModel::factory()->createElement();
        /** @var Entry $revision */
        $revision = ElementsFacade::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        postJson(action([DeleteElementController::class, 'destroy']), [
            'elementId' => $entry->id,
            'revisionId' => $revision->revisionId,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('deletes the canonical element when a provisional draft is requested', function () {
        $entry = EntryModel::factory()->createElement([
            'title' => 'Canonical Entry',
        ]);
        $draft = app(Drafts::class)->createDraft($entry, auth()->id(), provisional: true);

        postJson(action([DeleteElementController::class, 'destroy']), [
            'elementId' => $draft->id,
            'siteId' => $draft->siteId,
        ])->assertOk()
            ->assertJsonPath('message', t('{type} deleted.', ['type' => Entry::displayName()]));

        expect(Entry::find()->status(null)->id($entry->id)->trashed()->one()?->dateDeleted)
            ->not->toBeNull();
    });

    it('returns a failure response when deleting the element fails', function () {
        $entry = EntryModel::factory()->createElement();

        Event::listen(ElementLifecycleDeleting::class, function (ElementLifecycleDeleting $event) use ($entry) {
            if ($event->element->id === $entry->id) {
                $event->isValid = false;
            }
        });

        postJson(action([DeleteElementController::class, 'destroy']), [
            'elementId' => $entry->id,
            'siteId' => $entry->siteId,
        ])->assertBadRequest()
            ->assertJsonPath('message', t('Couldn’t delete {type}.', ['type' => $entry::lowerDisplayName()]));
    });
});

describe('destroyForSite', function () {
    it('returns 400 when no element is identified by the request', function () {
        postJson(action([DeleteElementController::class, 'destroyForSite']), [
            'elementType' => Entry::class,
            'siteId' => Sites::getPrimarySite()->id,
        ])->assertBadRequest();
    });

    it('returns 400 for revisions', function () {
        $secondarySite = Site::factory()->create();
        $section = Section::factory()
            ->withSites($secondarySite)
            ->withEntryTypes($entryType = EntryType::factory()->create())
            ->create([
                'propagationMethod' => PropagationMethod::Custom,
            ]);
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->enabledForSites($secondarySite)
            ->createElement();
        /** @var Entry $revision */
        $revision = ElementsFacade::getElementById(app(Revisions::class)->createRevision($entry, auth()->id()));

        postJson(action([DeleteElementController::class, 'destroyForSite']), [
            'elementId' => $entry->id,
            'revisionId' => $revision->revisionId,
            'siteId' => $entry->siteId,
        ])->assertBadRequest();
    });

    it('deletes only the requested site for canonical entries', function () {
        $secondarySite = Site::factory()->create();
        $section = Section::factory()
            ->withSites($secondarySite)
            ->withEntryTypes($entryType = EntryType::factory()->create())
            ->create([
                'propagationMethod' => PropagationMethod::Custom,
            ]);
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->enabledForSites($secondarySite)
            ->createElement([
                'title' => 'Multi-site entry',
            ]);

        postJson(action([DeleteElementController::class, 'destroyForSite']), [
            'elementId' => $entry->id,
            'siteId' => $secondarySite->id,
        ])->assertOk()
            ->assertJsonPath('message', t('{type} deleted for site.', ['type' => Entry::displayName()]));

        expect(entryQuery()->id($entry->id)->siteId($entry->siteId)->status(null)->exists())->toBeTrue()
            ->and(entryQuery()->id($entry->id)->siteId($secondarySite->id)->status(null)->exists())->toBeFalse();
    });

    it('returns the draft label when deleting a saved draft for a site', function () {
        $secondarySite = Site::factory()->create();
        $section = Section::factory()
            ->withSites($secondarySite)
            ->withEntryTypes($entryType = EntryType::factory()->create())
            ->create([
                'propagationMethod' => PropagationMethod::Custom,
            ]);
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->enabledForSites($secondarySite)
            ->createElement([
                'title' => 'Multi-site entry',
            ]);

        $secondaryEntry = entryQuery()
            ->id($entry->id)
            ->siteId($secondarySite->id)
            ->status(null)
            ->one();
        $draft = app(Drafts::class)->createDraft($secondaryEntry, auth()->id(), name: 'Site Draft');

        postJson(action([DeleteElementController::class, 'destroyForSite']), [
            'elementId' => $draft->id,
            'siteId' => $draft->siteId,
        ])->assertOk()
            ->assertJsonPath('message', t('{type} deleted for site.', ['type' => t('Draft')]));
    });

    it('deletes the canonical site when the current user has a provisional draft for that site', function () {
        $secondarySite = Site::factory()->create();
        $section = Section::factory()
            ->withSites($secondarySite)
            ->withEntryTypes($entryType = EntryType::factory()->create())
            ->create([
                'propagationMethod' => PropagationMethod::Custom,
            ]);
        $entry = EntryModel::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->enabledForSites($secondarySite)
            ->createElement([
                'title' => 'Multi-site entry',
            ]);

        $secondaryEntry = entryQuery()
            ->id($entry->id)
            ->siteId($secondarySite->id)
            ->status(null)
            ->one();

        $draft = app(Drafts::class)->createDraft($secondaryEntry, auth()->id(), provisional: true);

        postJson(action([DeleteElementController::class, 'destroyForSite']), [
            'elementId' => $entry->id,
            'siteId' => $secondarySite->id,
        ])->assertOk()
            ->assertJsonPath('message', t('{type} deleted for site.', ['type' => Entry::displayName()]));

        expect(entryQuery()->id($entry->id)->siteId($entry->siteId)->status(null)->exists())->toBeTrue()
            ->and(entryQuery()->id($entry->id)->siteId($secondarySite->id)->status(null)->exists())->toBeFalse()
            ->and(Entry::find()->id($draft->id)->drafts()->provisionalDrafts()->siteId($secondarySite->id)->status(null)->exists())->toBeFalse();
    });
});
