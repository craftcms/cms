<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\DraftCreated;
use CraftCms\Cms\Activity\EventTypes\ElementDuplicated;
use CraftCms\Cms\Activity\EventTypes\ElementMerged;
use CraftCms\Cms\Activity\EventTypes\ElementMoved;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Operations\ElementDuplicates;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Http\Controllers\Elements\ActivityTimelineController;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Structure\Enums\Mode;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

beforeEach(function () {
    actingAs(User::findOne());

    $this->activities = app(Activities::class);
});

it('records duplication instead of nested creation', function () {
    $source = EntryModel::factory()->createElement(['title' => 'Source entry']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $duplicate = app(ElementDuplicates::class)->duplicateElement($source);
    $events = $this->activities->query()->subject(ActivitySubject::fromElement($duplicate))->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->eventType)->toBe(ElementDuplicated::class)
        ->and($events->first()->data['source'])->toEqual([
            'type' => $source::class,
            'id' => $source->uid,
            'label' => $source->getUiLabel(),
        ])
        ->and($this->activities->format($events->first()))->toBe('Duplicated from Source entry.');
});

it('does not record draft creation for unpublished draft duplicates', function () {
    $source = EntryModel::factory()->createElement(['title' => 'Source entry']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    app(ElementDuplicates::class)->duplicateElement($source, asUnpublishedDraft: true);

    expect($this->activities->query()->eventTypes(DraftCreated::class)->get())->toBeEmpty();
});

it('records one duplication event for each affected site', function () {
    $otherSite = Site::factory()->create();
    Sites::refreshSites();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->withSites($otherSite)->create();
    $source = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $duplicate = app(ElementDuplicates::class)->duplicateElement($source);
    $events = $this->activities->query()
        ->subject(ActivitySubject::fromElement($duplicate))
        ->eventTypes(ElementDuplicated::class)
        ->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('siteId')->all())
        ->toEqualCanonicalizing([Sites::getPrimarySite()->id, $otherSite->id]);
});

it('records one event per bulk duplication subject', function () {
    $sources = EntryModel::factory()->count(2)->create();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $action = new Duplicate;
    $query = Entry::find()->id($sources->pluck('id'))->status(null);

    expect($action->performAction($query))->toBeTrue();

    $events = $this->activities->query()->eventTypes(ElementDuplicated::class)->get();

    expect($events)->toHaveCount(2)
        ->and($events->pluck('subjectId')->unique())->toHaveCount(2);
});

it('records captured structure movement positions', function () {
    [
        'structure' => $structure,
        'root' => $root,
        'children' => [$parent, $moved],
    ] = createStructureHierarchy();
    DB::table(Table::ACTIVITYEVENTS)->delete();

    expect(app(Structures::class)->append($structure->id, $moved, $parent))->toBeTrue();

    $event = $this->activities->query()
        ->subject(ActivitySubject::fromElement($moved))
        ->eventTypes(ElementMoved::class)
        ->firstOrFail();

    expect($event->data)->toMatchArray([
        'origin' => [
            'structure' => $structure->uid,
            'parent' => [
                'type' => $root::class,
                'id' => $root->uid,
                'label' => $root->getUiLabel(),
            ],
            'previousSibling' => [
                'type' => $parent::class,
                'id' => $parent->uid,
                'label' => $parent->getUiLabel(),
            ],
        ],
        'destination' => [
            'structure' => $structure->uid,
            'parent' => [
                'type' => $parent::class,
                'id' => $parent->uid,
                'label' => $parent->getUiLabel(),
            ],
            'previousSibling' => null,
        ],
    ])->and($this->activities->format($event))->toBe(
        "Moved from the position after {$parent->getUiLabel()} in {$root->getUiLabel()} to the first position in {$parent->getUiLabel()}.",
    );
});

it('shows parent changes in the activity timeline', function (bool $provisional) {
    $structure = Structure::factory()->create();
    $entryType = EntryType::factory()->create();
    $section = Section::factory()->withEntryTypes($entryType)->create([
        'type' => SectionType::Structure,
        'structureId' => $structure->id,
    ]);
    Sections::refreshSections();
    $parent = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Parent']);
    $moved = EntryModel::factory()->forSection($section)->forEntryType($entryType)->createElement(['title' => 'Moved']);
    app(Structures::class)->appendToRoot($structure->id, $parent, Mode::Insert);
    app(Structures::class)->appendToRoot($structure->id, $moved, Mode::Insert);
    $parent = structuredEntry($parent->id, $structure->id);
    $moved = structuredEntry($moved->id, $structure->id);
    $edited = $provisional
        ? app(Drafts::class)->createDraft($moved, User::findOne()->id, provisional: true)
        : $moved;
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $edited->setParentId($parent->id);
    expect(Elements::saveElement($edited, updateSearchIndex: false))->toBeTrue();

    if ($provisional) {
        app(Drafts::class)->applyDraft($edited);
    }

    expect(structuredEntry($moved->id, $structure->id)->getParentId())->toBe($parent->id);

    postJson(action(ActivityTimelineController::class), [
        'elementType' => Entry::class,
        'elementId' => $moved->id,
        'siteId' => $moved->siteId,
    ])
        ->assertOk()
        ->assertJsonPath(
            'events.0.description.text',
            'Moved from the position after Parent at the top level to the first position in Parent.',
        );
})->with([
    'canonical entry save' => false,
    'provisional draft application' => true,
]);

it('records both merge subjects without nested updates or deletion', function () {
    $merged = EntryModel::factory()->createElement(['title' => 'Merged entry']);
    $prevailing = EntryModel::factory()->createElement(['title' => 'Prevailing entry']);
    DB::table(Table::ACTIVITYEVENTS)->delete();
    Queue::fake();

    expect(app(ElementDeletions::class)->mergeElements($merged, $prevailing))->toBeTrue();

    $events = $this->activities->query()->get();
    $mergedEvent = $events->firstWhere('subjectId', $merged->uid);
    $prevailingEvent = $events->firstWhere('subjectId', $prevailing->uid);

    expect($events)->toHaveCount(2)
        ->and($events->pluck('eventType')->unique()->all())->toBe([ElementMerged::class])
        ->and($events->pluck('subjectId')->all())->toEqualCanonicalizing([$merged->uid, $prevailing->uid])
        ->and($this->activities->format($mergedEvent))->toBe('Merged into Prevailing entry.')
        ->and($this->activities->format($prevailingEvent))->toBe('Merged Merged entry into this element.');
});
