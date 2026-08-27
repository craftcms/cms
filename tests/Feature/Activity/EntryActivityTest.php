<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->activities = app(Activities::class);
});

it('records entry creation once for each supported site', function () {
    $otherSite = Site::factory()->create();
    Sites::refreshSites();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($otherSite)->create();

    post(action(StoreEntryController::class), [
        'sectionId' => $section->id,
        'typeId' => $entryType->id,
        'title' => 'New entry',
        'enabled' => true,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $entry = Entry::find()->sectionId($section->id)->title('New entry')->status(null)->one();

    $events = $this->activities->query()
        ->subject($entry)
        ->eventTypes('craft.element.created')
        ->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('siteId')->all())
        ->toEqualCanonicalizing([Sites::getPrimarySite()->id, $otherSite->id]);
});

it('records normalized entry content changes', function () {
    $result = EntryModel::factory()
        ->withField('bodyField', PlainText::class, value: 'Old body')
        ->createElementWithFields(['title' => 'Old title']);
    $entry = $result->element;
    $field = $entry->getFieldLayout()->getFieldByHandle('bodyField');
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $entry->title = 'New title';
    $entry->setFieldValue($field->handle, 'New body');

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();

    $event = $this->activities->query()
        ->subject($entry)
        ->eventTypes('craft.element.updated')
        ->firstOrFail();

    expect($event->changes)->toBe([
        [
            'type' => 'attribute',
            'id' => 'title',
            'label' => 'Title',
            'old' => 'Old title',
            'new' => 'New title',
        ],
        [
            'type' => 'field',
            'id' => $field->layoutElement->uid,
            'label' => $field->name,
            'old' => 'Old body',
            'new' => 'New body',
        ],
    ]);
});

it('records a status change instead of a generic update', function () {
    $result = EntryModel::factory()
        ->withField('bodyField', PlainText::class, value: 'Old body')
        ->createElementWithFields();
    $entry = $result->element;
    $field = $entry->getFieldLayout()->getFieldByHandle('bodyField');
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $entry->setEnabledForSite(false);
    $entry->setFieldValue($field->handle, 'New body');

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();

    $events = $this->activities->query()->subject($entry)->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->eventType)->toBe('craft.element.status-changed')
        ->and($events->first()->data)->toBe(['oldStatus' => 'live', 'newStatus' => 'disabled'])
        ->and($this->activities->format($events->first()))->toBe('Status changed from Live to Disabled.')
        ->and($events->first()->changes)->toContain([
            'type' => 'field',
            'id' => $field->layoutElement->uid,
            'label' => $field->name,
            'old' => 'Old body',
            'new' => 'New body',
        ]);
});

it('records an update while omitting unsafe field values', function () {
    $result = EntryModel::factory()
        ->withField('bodyField', PlainText::class, value: 'Old body')
        ->createElementWithFields(['title' => 'Old title']);
    $entry = $result->element;
    $field = $entry->getFieldLayout()->getFieldByHandle('bodyField');
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $entry->setFieldValue($field->handle, '<strong>Rendered HTML</strong>');

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();

    $event = $this->activities->query()->subject($entry)->firstOrFail();

    expect($event->eventType)->toBe('craft.element.updated')
        ->and($event->changes)->toBeEmpty();
});

it('does not record cancelled saves', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $entry->title = 'Cancelled title';

    Event::listen(function (ElementSaving $event) use ($entry) {
        if ($event->element === $entry) {
            $event->isValid = false;
        }
    });

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeFalse()
        ->and($this->activities->query()->subject($entry)->get())->toBeEmpty();
});

it('does not record no-op, draft, resave, or rolled-back work', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Original title',
        'expiryDate' => now()->addDay(),
    ]);
    $entry->expiryDate = Date::parse($entry->expiryDate->format(DATE_ATOM));
    $entry->setDirtyAttributes(['expiryDate']);

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue()
        ->and($this->activities->query()->subject($entry)->get())->toBeEmpty();

    app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);

    $entry->resaving = true;
    $entry->title = 'Resaved title';
    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue()
        ->and($this->activities->query()->subject($entry)->get())->toBeEmpty();

    $entry = Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one();
    $entry->title = 'Rolled back title';

    DB::beginTransaction();
    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();
    DB::rollBack();

    expect($this->activities->query()->subject($entry)->get())->toBeEmpty()
        ->and(Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one()->title)
        ->toBe('Resaved title');
});

it('records draft work against the canonical entry', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, name: 'Campaign draft');

    $draft->title = 'Draft title';
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue();

    app(Drafts::class)->applyDraft($draft);

    $events = $this->activities->query()->subject($entry)->get();

    expect($events->pluck('eventType')->all())->toBe([
        'craft.draft.applied',
        'craft.draft.saved',
        'craft.draft.created',
    ])->and($events->pluck('siteId')->unique()->all())->toBe([$entry->siteId]);
});

it('records applying a provisional draft as an entry update', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft->title = 'Updated title';
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue();

    app(Drafts::class)->applyDraft($draft);

    $event = $this->activities->query()->subject($entry)->sole();

    expect($event->eventType)->toBe('craft.element.updated')
        ->and($event->snapshots['subject']['label'])->toBe('Updated title')
        ->and($event->changes)->toContain([
            'type' => 'attribute',
            'id' => 'title',
            'label' => 'Title',
            'old' => 'Original title',
            'new' => 'Updated title',
        ]);
});

it('records draft creation and its initial save', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    postJson(action([ElementDraftsController::class, 'store']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'title' => 'Draft title',
    ])->assertOk();

    $events = $this->activities->query()->subject($entry)->get();

    expect($events->pluck('eventType')->all())->toBe(['craft.draft.saved', 'craft.draft.created']);
});

it('ignores provisional creation and autosave but records its discard', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, name: 'Discard me');
    expect(app(Drafts::class)->discardDraft($draft))->toBeTrue();

    $provisional = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    $provisional->title = 'Autosaved title';
    expect(Elements::saveElement($provisional, updateSearchIndex: false))->toBeTrue();
    expect(app(Drafts::class)->discardDraft($provisional))->toBeTrue();

    expect($this->activities->query()->subject($entry)->pluck('eventType')->all())->toBe([
        'craft.draft.discarded',
        'craft.draft.discarded',
        'craft.draft.created',
    ]);
});

it('records an explicitly saved unpublished draft as created', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(app(Drafts::class)->saveElementAsDraft($entry, User::findOne()->id))->toBeTrue();

    $event = $this->activities->query()->subject($entry)->firstOrFail();

    expect($event->eventType)->toBe('craft.draft.created')
        ->and($event->siteId)->toBe($entry->siteId);
});

it('records provisional draft promotion as creation and ignores no-op saves', function () {
    $entry = EntryModel::factory()->createElement();
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft->isProvisionalDraft = false;
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue();

    $event = $this->activities->query()->subject($entry)->sole();

    expect($event->eventType)->toBe('craft.draft.created');

    DB::table(Table::ACTIVITYEVENTS)->delete();
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue()
        ->and($this->activities->query()->subject($entry)->get())->toBeEmpty();
});

it('records revision restoration without a generic update', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $revisionId = app(Revisions::class)->createRevision($entry, User::findOne()->id, force: true);
    $revision = Entry::find()->id($revisionId)->revisions()->status(null)->one();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    app(Revisions::class)->revertToRevision($revision, User::findOne()->id);

    $events = $this->activities->query()->subject($entry)->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->eventType)->toBe('craft.revision.restored')
        ->and($events->first()->data)->toBe(['revisionNum' => $revision->revisionNum])
        ->and($events->first()->siteId)->toBe($entry->siteId)
        ->and($this->activities->format($events->first()))->toBe("Restored revision {$revision->revisionNum}.");
});
