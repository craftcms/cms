<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\Activities;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\Duplicate;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Operations\ElementDuplicates;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Structure\Structures;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->activities = app(Activities::class);
});

it('records duplication instead of nested creation', function () {
    $source = EntryModel::factory()->createElement(['title' => 'Source entry']);
    DB::table(Table::ACTIVITYEVENTS)->delete();

    $duplicate = app(ElementDuplicates::class)->duplicateElement($source);
    $events = $this->activities->query()->subject($duplicate)->get();

    expect($events)->toHaveCount(1)
        ->and($events->first()->eventType)->toBe('craft.element.duplicated')
        ->and($events->first()->data['source'])->toBe([
            'type' => $source::class,
            'id' => $source->uid,
            'label' => $source->getUiLabel(),
        ])
        ->and($this->activities->format($events->first()))->toBe('Duplicated from Source entry.');
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
        ->subject($duplicate)
        ->eventTypes('craft.element.duplicated')
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

    $events = $this->activities->query()->eventTypes('craft.element.duplicated')->get();

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
        ->subject($moved)
        ->eventTypes('craft.element.moved')
        ->firstOrFail();

    expect($event->data)->toBe([
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

it('does not record technical structure movement', function () {
    [
        'structure' => $structure,
        'children' => [$parent, $moved],
    ] = createStructureHierarchy();
    DB::table(Table::ACTIVITYEVENTS)->delete();
    $moved->resaving = true;

    expect(app(Structures::class)->append($structure->id, $moved, $parent))->toBeTrue()
        ->and($this->activities->query()->get())->toBeEmpty();
});

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
        ->and($events->pluck('eventType')->unique()->all())->toBe(['craft.element.merged'])
        ->and($events->pluck('subjectId')->all())->toEqualCanonicalizing([$merged->uid, $prevailing->uid])
        ->and($this->activities->format($mergedEvent))->toBe('Merged into Prevailing entry.')
        ->and($this->activities->format($prevailingEvent))->toBe('Merged Merged entry into this element.');
});
