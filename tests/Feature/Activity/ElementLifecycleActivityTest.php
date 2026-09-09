<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementDeleted as ElementDeletedActivity;
use CraftCms\Cms\Activity\EventTypes\ElementRestored as ElementRestoredActivity;
use CraftCms\Cms\Activity\EventTypes\ElementSiteAdded;
use CraftCms\Cms\Activity\EventTypes\ElementSiteRemoved;
use CraftCms\Cms\Activity\EventTypes\ElementTrashed;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\ElementDeleted;
use CraftCms\Cms\Element\Events\ElementDeletedForSite;
use CraftCms\Cms\Element\Events\ElementDeleting;
use CraftCms\Cms\Element\Events\ElementSaved;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->activities = app(Activities::class);
});

it('records trash restore and permanent deletion with durable snapshots', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Release notes']);
    $subject = ActivitySubject::fromElement($entry);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(Elements::deleteElement($entry))->toBeTrue();

    $entry = Entry::find()->id($entry->id)->siteId($entry->siteId)->trashed()->one();

    expect(Elements::restoreElement($entry))->toBeTrue()
        ->and(Elements::deleteElement($entry, true))->toBeTrue();

    $events = $this->activities->query()->subject($subject)->get()->reverse()->values();

    expect($events->pluck('eventType')->all())->toBe([
        ElementTrashed::class,
        ElementRestoredActivity::class,
        ElementDeletedActivity::class,
    ])->and($events->pluck('siteId')->unique()->all())->toBe([$entry->siteId])
        ->and($events->pluck('snapshots.subject.label')->unique()->all())->toBe(['Release notes'])
        ->and(Entry::find()->id($entry->id)->status(null)->trashed(null)->exists())->toBeFalse();
});

it('does not record cancelled no-op or rolled-back lifecycle actions', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();
    $cancelNextDelete = true;

    Event::listen(function (ElementDeleting $event) use ($entry, &$cancelNextDelete) {
        if (! $cancelNextDelete || $event->element !== $entry) {
            return;
        }

        $event->isValid = false;
        $cancelNextDelete = false;
    });

    expect(Elements::deleteElement($entry))->toBeFalse()
        ->and($this->activities->query()->get())->toBeEmpty();

    expect(Elements::restoreElement($entry))->toBeTrue()
        ->and($this->activities->query()->get())->toBeEmpty();

    DB::beginTransaction();
    expect(Elements::deleteElement($entry))->toBeTrue();
    DB::rollBack();

    expect($this->activities->query()->get())->toBeEmpty();

    $entry = Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->one();
    expect(Elements::deleteElement($entry))->toBeTrue();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(Elements::deleteElement($entry))->toBeTrue()
        ->and($this->activities->query()->get())->toBeEmpty();

    expect(Elements::deleteElement($entry, true))->toBeTrue();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(Elements::deleteElement($entry, true))->toBeFalse()
        ->and(Elements::restoreElement($entry))->toBeFalse()
        ->and($this->activities->query()->get())->toBeEmpty();
});

it('dispatches the post-delete event within a surrounding transaction', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Event::listen(function (ElementDeleted $event) use ($entry) {
        if ($event->element === $entry) {
            throw new RuntimeException('Deletion failed.');
        }
    });

    expect(fn () => DB::transaction(fn () => Elements::deleteElement($entry)))
        ->toThrow(RuntimeException::class, 'Deletion failed.');

    expect($this->activities->query()->eventTypes(ElementTrashed::class)->count())->toBe(0)
        ->and(Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->exists())->toBeTrue();
});

it('records one event per restored element', function () {
    $entries = [
        EntryModel::factory()->createElement(),
        EntryModel::factory()->createElement(),
    ];

    foreach ($entries as $entry) {
        Elements::deleteElement($entry);
    }

    $entries = Entry::find()
        ->id(array_column($entries, 'id'))
        ->siteId($entries[0]->siteId)
        ->trashed()
        ->all();

    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(Elements::restoreElements($entries))->toBeTrue();

    $events = $this->activities->query()->eventTypes(ElementRestoredActivity::class)->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('subjectId')->unique())->toHaveCount(2);
});

it('records site removal and addition without generic propagation events', function () {
    [$entry, $secondarySite] = createLifecycleMultiSiteEntry();
    $secondaryEntry = Entry::find()
        ->id($entry->id)
        ->siteId($secondarySite->id)
        ->status(null)
        ->one();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Elements::deleteElementForSite($secondaryEntry);
    expect(Elements::saveElement($entry, updateSearchIndex: false))->toBeTrue();

    $events = $this->activities->query()->subject(ActivitySubject::fromElement($entry))->get()->reverse()->values();

    expect($events->pluck('eventType')->all())->toBe([
        ElementSiteRemoved::class,
        ElementSiteAdded::class,
    ])->and($events->pluck('siteId')->all())->toBe([
        $secondarySite->id,
        $secondarySite->id,
    ])->and($events->pluck('snapshots.site.name')->unique()->all())->toBe(['Secondary Site'])
        ->and($this->activities->format($events[0]))->toBe('Removed from Secondary Site.')
        ->and($this->activities->format($events[1]))->toBe('Added to Secondary Site.');
});

it('keeps actor labels after the actor is permanently deleted', function () {
    $admin = User::findOne();
    $actor = UserModel::factory()->createElement(['fullName' => 'Ada Lovelace']);
    $entry = EntryModel::factory()->createElement();
    $subject = ActivitySubject::fromElement($entry);
    actingAs($actor);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(Elements::deleteElement($entry))->toBeTrue();

    actingAs($admin);
    expect(Elements::deleteElement($actor, true))->toBeTrue()
        ->and(User::find()->id($actor->id)->status(null)->exists())->toBeFalse();

    $event = $this->activities->query()->subject($subject)->firstOrFail();

    expect($event->snapshots['actor']['label'])->toBe('Ada Lovelace');
});

it('rolls back site removal when its post-delete event fails', function () {
    [$entry, $secondarySite] = createLifecycleMultiSiteEntry();
    $secondaryEntry = Entry::find()
        ->id($entry->id)
        ->siteId($secondarySite->id)
        ->status(null)
        ->one();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Event::listen(function (ElementDeletedForSite $event) use ($secondaryEntry) {
        if ($event->element === $secondaryEntry) {
            throw new RuntimeException('Site deletion failed.');
        }
    });

    expect(fn () => Elements::deleteElementForSite($secondaryEntry))
        ->toThrow(RuntimeException::class, 'Site deletion failed.');

    expect(Entry::find()->id($secondaryEntry->id)->siteId($secondaryEntry->siteId)->status(null)->exists())->toBeTrue()
        ->and($this->activities->query()->eventTypes(ElementSiteRemoved::class)->count())->toBe(0);
});

it('rolls back single-site deletion when its post-delete event fails', function () {
    $entry = EntryModel::factory()->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Event::listen(function (ElementDeleted $event) use ($entry) {
        if ($event->element === $entry) {
            throw new RuntimeException('Site deletion failed.');
        }
    });

    expect(fn () => Elements::deleteElementForSite($entry))
        ->toThrow(RuntimeException::class, 'Site deletion failed.');

    expect(Entry::find()->id($entry->id)->siteId($entry->siteId)->status(null)->exists())->toBeTrue()
        ->and($this->activities->query()->eventTypes(ElementDeletedActivity::class)->count())->toBe(0);
});

it('dispatches the post-save event within a surrounding transaction', function () {
    [$entry, $secondarySite] = createLifecycleMultiSiteEntry();
    $secondaryEntry = Entry::find()
        ->id($entry->id)
        ->siteId($secondarySite->id)
        ->status(null)
        ->one();
    Elements::deleteElementForSite($secondaryEntry);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    Event::listen(function (ElementSaved $event) use ($entry) {
        if ($event->element === $entry) {
            throw new RuntimeException('Save failed.');
        }
    });

    expect(fn () => DB::transaction(fn () => Elements::saveElement($entry, updateSearchIndex: false)))
        ->toThrow(RuntimeException::class, 'Save failed.');

    expect($this->activities->query()->eventTypes(ElementSiteAdded::class)->count())->toBe(0)
        ->and(Entry::find()->id($entry->id)->siteId($secondarySite->id)->status(null)->exists())->toBeFalse();
});

function createLifecycleMultiSiteEntry(): array
{
    $secondarySite = Site::factory()->create([
        'handle' => 'secondary',
        'name' => 'Secondary Site',
    ]);

    Sites::refreshSites();

    $section = Section::factory()->withEntryTypes(
        $entryType = EntryType::factory()->create(),
    )->create([
        'propagationMethod' => PropagationMethod::Custom,
    ]);

    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondarySite->id,
        'hasUrls' => true,
        'dateCreated' => $section->dateCreated,
        'dateUpdated' => $section->dateUpdated,
    ]);

    app(Fields::class)->invalidateCaches();
    app(Fields::class)->refreshFields();

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Multi-site entry']);

    $entry->setEnabledForSite([
        $entry->siteId => true,
        $secondarySite->id => true,
    ]);
    Elements::saveElement($entry);

    return [$entry, $secondarySite];
}
