<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivityChange;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\DraftApplied;
use CraftCms\Cms\Activity\EventTypes\DraftCreated;
use CraftCms\Cms\Activity\EventTypes\DraftDiscarded;
use CraftCms\Cms\Activity\EventTypes\DraftSaved;
use CraftCms\Cms\Activity\EventTypes\ElementCreated;
use CraftCms\Cms\Activity\EventTypes\ElementStatusChanged;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\EventTypes\RevisionRestored;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Element\Events\ElementSaving;
use CraftCms\Cms\Element\Events\RevertedToRevision;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\Http\Controllers\Elements\ActivityTimelineController;
use CraftCms\Cms\Http\Controllers\Elements\ElementDraftsController;
use CraftCms\Cms\Http\Controllers\Entries\StoreEntryController;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
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
        ->subject(ActivitySubject::fromElement($entry))
        ->eventTypes(ElementCreated::class)
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
        ->subject(ActivitySubject::fromElement($entry))
        ->eventTypes(ElementUpdated::class)
        ->firstOrFail();

    expect($event->changes)->toEqualCanonicalizing([
        new ActivityChange('Title', 'Old title', 'New title'),
        new ActivityChange($field->name, 'Old body', 'New body'),
    ]);
});

it('shows author and relational changes in the activity timeline', function () {
    $oldAuthor = User::findOne();
    $author = UserModel::factory()->createElement(['fullName' => 'Ada Lovelace']);
    $oldTarget = EntryModel::factory()->createElement(['title' => 'Old target']);
    $newTarget = EntryModel::factory()->createElement(['title' => 'New target']);
    $result = EntryModel::factory()
        ->withField('relatedEntries', EntriesField::class, value: [$oldTarget->id])
        ->createElementWithFields();
    $entry = $result->element;
    $field = $result->fields->get('relatedEntries');
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $entry->setAuthorIds([$author->id]);
    $entry->setFieldValue($field->handle, [$newTarget->id]);

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();

    $changes = collect(postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])->assertOk()->json('events.0.changes'))
        ->keyBy('label');

    expect($changes['Authors']['old'])->toEqual([[
        'elementType' => User::class,
        'id' => $oldAuthor->id,
        'label' => $oldAuthor->getUiLabel(),
        'siteId' => $oldAuthor->siteId,
    ]])
        ->and($changes['Authors']['new'])->toEqual([[
            'elementType' => User::class,
            'id' => $author->id,
            'label' => 'Ada Lovelace',
            'siteId' => $author->siteId,
        ]])
        ->and($changes[$field->name]['old'])->toEqual([[
            'elementType' => Entry::class,
            'id' => $oldTarget->id,
            'label' => 'Old target',
            'siteId' => $oldTarget->siteId,
        ]])
        ->and($changes[$field->name]['new'])->toEqual([[
            'elementType' => Entry::class,
            'id' => $newTarget->id,
            'label' => 'New target',
            'siteId' => $newTarget->siteId,
        ]]);
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

    $events = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->eventType)->toBe(ElementStatusChanged::class)
        ->and($events->first()->data)->toEqual(['oldStatus' => 'live', 'newStatus' => 'disabled'])
        ->and($this->activities->format($events->first()))->toBe('Status changed from Live to Disabled.')
        ->and($events->first()->changes)->toContainEqual(
            new ActivityChange($field->name, 'Old body', 'New body'),
        );
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

    $event = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->firstOrFail();

    expect($event->eventType)->toBe(ElementUpdated::class)
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
        ->and($this->activities->query()->subject(ActivitySubject::fromElement($entry))->get())->toBeEmpty();
});

it('does not record no-op, draft, resave, or rolled-back work', function () {
    $entry = EntryModel::factory()->createElement([
        'title' => 'Original title',
        'expiryDate' => now()->addDay(),
    ]);
    $entry->expiryDate = Date::parse($entry->expiryDate->format(DATE_ATOM));
    $entry->setDirtyAttributes(['expiryDate']);

    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue()
        ->and($this->activities->query()->subject(ActivitySubject::fromElement($entry))->get())->toBeEmpty();

    app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    expect($this->activities->query()->subject(ActivitySubject::fromElement($entry))->get())->toBeEmpty();

    Elements::resaveElements(
        Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null),
        updateSearchIndex: false,
    );
    expect($this->activities->query()->subject(ActivitySubject::fromElement($entry))->get())->toBeEmpty();

    $entry = Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one();
    $entry->title = 'Rolled back title';

    DB::beginTransaction();
    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();
    DB::rollBack();

    expect($this->activities->query()->subject(ActivitySubject::fromElement($entry))->get())->toBeEmpty()
        ->and(Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one()->title)
        ->toBe('Original title');
});

it('records draft work against the canonical entry', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, name: 'Campaign draft');

    $draft->title = 'Draft title';
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue();

    app(Drafts::class)->applyDraft($draft);

    $events = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->get();

    expect($events->pluck('eventType')->all())->toBe([
        DraftApplied::class,
        DraftSaved::class,
        DraftCreated::class,
    ])->and($events->pluck('siteId')->unique()->all())->toBe([$entry->siteId]);
});

it('records applying a provisional draft as an entry update', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft->title = 'Updated title';
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue();

    app(Drafts::class)->applyDraft($draft);

    $event = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->sole();

    expect($event->eventType)->toBe(ElementUpdated::class)
        ->and($event->snapshots['subject']['label'])->toBe('Updated title')
        ->and($event->changes)->toContainEqual(
            new ActivityChange('Title', 'Original title', 'Updated title'),
        );
});

it('shows author and relational changes applied from provisional drafts', function () {
    $firstTarget = EntryModel::factory()->createElement(['title' => 'First target']);
    $secondTarget = EntryModel::factory()->createElement(['title' => 'Second target']);
    $result = EntryModel::factory()
        ->withField('relatedEntries', EntriesField::class)
        ->createElementWithFields();
    $entry = $result->element;
    $field = $result->fields->get('relatedEntries');
    $oldAuthor = User::findOne();
    $entry->setAuthorIds([$oldAuthor->id]);
    Elements::saveElement($entry, updateSearchIndex: false);
    $newAuthor = UserModel::factory()->createElement(['fullName' => 'Ada Lovelace']);
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft->setAuthorIds([$newAuthor->id]);
    $draft->setFieldValue($field->handle, [$firstTarget->id, $secondTarget->id]);
    expect(Elements::saveElement($draft, updateSearchIndex: false))->toBeTrue();

    app(Drafts::class)->applyDraft($draft);

    $changes = collect(postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
    ])->assertOk()->json('events.0.changes'))
        ->keyBy('label');

    expect($changes['Authors']['old'])->toEqual([[
        'elementType' => User::class,
        'id' => $oldAuthor->id,
        'label' => $oldAuthor->getUiLabel(),
        'siteId' => $oldAuthor->siteId,
    ]])
        ->and($changes['Authors']['new'])->toEqual([[
            'elementType' => User::class,
            'id' => $newAuthor->id,
            'label' => 'Ada Lovelace',
            'siteId' => $newAuthor->siteId,
        ]])
        ->and($changes[$field->name]['old'])->toBe([])
        ->and($changes[$field->name]['new'])->toEqual([
            [
                'elementType' => Entry::class,
                'id' => $firstTarget->id,
                'label' => 'First target',
                'siteId' => $firstTarget->siteId,
            ],
            [
                'elementType' => Entry::class,
                'id' => $secondTarget->id,
                'label' => 'Second target',
                'siteId' => $secondTarget->siteId,
            ],
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

    $events = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->get();

    expect($events->pluck('eventType')->all())->toBe([DraftSaved::class, DraftCreated::class]);
});

it('records named draft discard through the endpoint', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, name: 'Discard me');
    postJson(action([ElementDraftsController::class, 'destroy']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'draftId' => $draft->draftId,
    ])->assertOk();

    expect($this->activities->query()->subject(ActivitySubject::fromElement($entry))->pluck('eventType')->all())->toBe([
        DraftDiscarded::class,
        DraftCreated::class,
    ]);
});

it('ignores provisional creation and autosave but records its endpoint discard', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $provisional = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    $provisional->title = 'Autosaved title';
    expect(Elements::saveElement($provisional, updateSearchIndex: false))->toBeTrue();
    postJson(action([ElementDraftsController::class, 'destroy']), [
        'elementType' => Entry::class,
        'elementId' => $entry->id,
        'siteId' => $entry->siteId,
        'draftId' => $provisional->draftId,
        'provisional' => 1,
    ])->assertOk();

    expect($this->activities->query()->subject(ActivitySubject::fromElement($entry))->pluck('eventType')->all())->toBe([
        DraftDiscarded::class,
    ]);
});

it('records an explicitly saved unpublished draft as created', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(app(Drafts::class)->saveElementAsDraft($entry, User::findOne()->id))->toBeTrue();

    $event = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->firstOrFail();

    expect($event->eventType)->toBe(DraftCreated::class)
        ->and($event->siteId)->toBe($entry->siteId);
});

it('records provisional draft promotion as creation and ignores no-op saves', function () {
    $entry = EntryModel::factory()->createElement();
    $draft = app(Drafts::class)->createDraft($entry, User::findOne()->id, provisional: true);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $payload = [
        'elementType' => Entry::class,
        'draftId' => $draft->draftId,
        'siteId' => $draft->siteId,
        'title' => 'Saved draft',
    ];

    postJson(action([ElementDraftsController::class, 'store']), [
        ...$payload,
        'dropProvisional' => true,
    ])->assertOk();

    $event = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->sole();

    expect($event->eventType)->toBe(DraftCreated::class);

    DB::table(Table::ACTIVITYEVENTS)->delete();
    postJson(action([ElementDraftsController::class, 'store']), $payload)->assertOk();

    expect($this->activities->query()->subject(ActivitySubject::fromElement($entry))->get())->toBeEmpty();
});

it('records revision restoration without a generic update', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $revisionId = app(Revisions::class)->createRevision($entry, User::findOne()->id, force: true);
    $revision = Entry::find()->id($revisionId)->revisions()->status(null)->one();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    app(Revisions::class)->revertToRevision($revision, User::findOne()->id);

    $events = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->eventType)->toBe(RevisionRestored::class)
        ->and($events->first()->data)->toBe(['revisionNum' => $revision->revisionNum])
        ->and($events->first()->siteId)->toBe($entry->siteId)
        ->and($this->activities->format($events->first()))->toBe("Restored revision {$revision->revisionNum}.");
});

it('rolls back revision restoration when a post-save event fails', function (string $eventType) {
    $entry = EntryModel::factory()->createElement(['title' => 'Original title']);
    $revisionId = app(Revisions::class)->createRevision($entry, User::findOne()->id, force: true);
    $revision = Entry::find()->id($revisionId)->revisions()->status(null)->one();
    $entry->title = 'Current title';
    Elements::saveElement($entry, updateSearchIndex: false);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Event::listen($eventType, function (object $event) use ($entry) {
        if (! $event instanceof ElementSaved || $event->element->id === $entry->id) {
            throw new RuntimeException('Post-save event failed.');
        }
    });

    expect(fn () => app(Revisions::class)->revertToRevision($revision, User::findOne()->id))
        ->toThrow(RuntimeException::class, 'Post-save event failed.');

    expect(Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one()->title)->toBe('Current title')
        ->and($this->activities->query()->eventTypes(RevisionRestored::class)->count())->toBe(0);
})->with([ElementSaved::class, RevertedToRevision::class]);
