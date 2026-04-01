<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\MergeElementsAction;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\AfterMergeElements;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Search\Jobs\FindAndReplace;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->field = Field::factory()->create([
        'handle' => 'relatedEntries',
        'type' => Entries::class,
    ]);

    $this->fieldLayout = FieldLayout::factory()->forField($this->field)->create();

    $this->section = Section::factory()->create([
        'handle' => 'mergeTestSection',
    ]);

    $this->entryType = EntryType::factory()->create([
        'fieldLayoutId' => $this->fieldLayout->id,
    ]);

    app(Fields::class)->invalidateCaches();
    app(Fields::class)->refreshFields();

    $this->mergedEntryModel = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->title('Merged entry')
        ->create();
    $this->mergedEntryModel->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

    $this->prevailingEntryModel = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->title('Prevailing entry')
        ->create();
    $this->prevailingEntryModel->element->update(['fieldLayoutId' => $this->fieldLayout->id]);

    $this->mergedEntry = entryQuery()->id($this->mergedEntryModel->id)->status(null)->firstOrFail();
    $this->prevailingEntry = entryQuery()->id($this->prevailingEntryModel->id)->status(null)->firstOrFail();
});

function createRelatedEntrySource(Section $section, EntryType $entryType, FieldLayout $fieldLayout): EntryElement
{
    $entryModel = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->create();

    $entryModel->element->update(['fieldLayoutId' => $fieldLayout->id]);

    return entryQuery()->id($entryModel->id)->status(null)->firstOrFail();
}

test('replaces related field values with the prevailing element id', function () {
    $source = createRelatedEntrySource($this->section, $this->entryType, $this->fieldLayout);
    $source->setFieldValue('relatedEntries', [$this->mergedEntry->id]);

    app(Elements::class)->saveElement($source);

    Queue::fake();
    Event::fake([AfterMergeElements::class]);

    $success = app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    $reloadedSource = entryQuery()->id($source->id)->status(null)->firstOrFail();
    $relations = DB::table(Table::RELATIONS)
        ->where('sourceId', $source->id)
        ->where('fieldId', $this->field->id)
        ->orderBy('sortOrder')
        ->pluck('targetId')
        ->all();

    expect($success)->toBeTrue()
        ->and($relations)->toBe([$this->prevailingEntry->id])
        ->and(DB::table(Table::ELEMENTS)->where('id', $this->mergedEntry->id)->value('dateDeleted'))->not->toBeNull();

    Event::assertDispatched(fn (AfterMergeElements $event) => $event->mergedElementId === $this->mergedEntry->id
        && $event->prevailingElementId === $this->prevailingEntry->id);
});

test('deduplicates related field values when the prevailing element is already selected', function () {
    $source = createRelatedEntrySource($this->section, $this->entryType, $this->fieldLayout);
    $source->setFieldValue('relatedEntries', [$this->mergedEntry->id, $this->prevailingEntry->id]);

    app(Elements::class)->saveElement($source);

    Queue::fake();

    app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    $reloadedSource = entryQuery()->id($source->id)->status(null)->firstOrFail();

    expect($reloadedSource->getFieldValue('relatedEntries')->ids())->toBe([$this->prevailingEntry->id]);
});

test('updates remaining relation rows to the prevailing element id', function () {
    $source = createRelatedEntrySource($this->section, $this->entryType, $this->fieldLayout);

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $this->field->id,
        'sourceId' => $source->id,
        'sourceSiteId' => $source->siteId,
        'targetId' => $this->mergedEntry->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    Queue::fake();

    app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    expect(DB::table(Table::RELATIONS)
        ->where('sourceId', $source->id)
        ->where('fieldId', $this->field->id)
        ->pluck('targetId')
        ->all())->toBe([$this->prevailingEntry->id]);
});

test('updates structure rows to the prevailing element id', function () {
    $structureId = DB::table(Table::STRUCTURES)->insertGetId([
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    DB::table(Table::STRUCTUREELEMENTS)->insert([
        'elementId' => $this->mergedEntry->id,
        'structureId' => $structureId,
        'lft' => 1,
        'rgt' => 2,
        'level' => 0,
        'uid' => Str::uuid()->toString(),
        'dateCreated' => now(),
        'dateUpdated' => now(),
    ]);

    Queue::fake();

    app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    expect(DB::table(Table::STRUCTUREELEMENTS)
        ->where('structureId', $structureId)
        ->pluck('elementId')
        ->all())->toBe([$this->prevailingEntry->id]);
});

test('deletes duplicate structure rows when the prevailing element is already in the structure', function () {
    $structureId = DB::table(Table::STRUCTURES)->insertGetId([
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    DB::table(Table::STRUCTUREELEMENTS)->insert([
        [
            'elementId' => $this->mergedEntry->id,
            'structureId' => $structureId,
            'lft' => 1,
            'rgt' => 2,
            'level' => 0,
            'uid' => Str::uuid()->toString(),
            'dateCreated' => now(),
            'dateUpdated' => now(),
        ],
        [
            'elementId' => $this->prevailingEntry->id,
            'structureId' => $structureId,
            'lft' => 3,
            'rgt' => 4,
            'level' => 0,
            'uid' => Str::uuid()->toString(),
            'dateCreated' => now(),
            'dateUpdated' => now(),
        ],
    ]);

    Queue::fake();

    app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    expect(DB::table(Table::STRUCTUREELEMENTS)
        ->where('structureId', $structureId)
        ->count())->toBe(1)
        ->and(DB::table(Table::STRUCTUREELEMENTS)
            ->where('structureId', $structureId)
            ->value('elementId'))->toBe($this->prevailingEntry->id);
});

test('dispatches find and replace jobs for entry reference tags', function () {
    Queue::fake();

    app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    Queue::assertPushed(FindAndReplace::class, fn (FindAndReplace $job) => $job->find === '{entry:'.$this->mergedEntry->id.':'
        && $job->replace === '{entry:'.$this->prevailingEntry->id.':');

    Queue::assertPushed(FindAndReplace::class, fn (FindAndReplace $job) => $job->find === '{entry:'.$this->mergedEntry->id.'}'
        && $job->replace === '{entry:'.$this->prevailingEntry->id.'}');

    Queue::assertPushed(FindAndReplace::class, 2);
});

test('queries unique related elements when a relation source site id is null', function () {
    $secondSite = Site::factory()->create();
    Sites::refreshSites();

    $sourceModel = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->create();
    $sourceModel->element->update(['fieldLayoutId' => $this->fieldLayout->id]);
    $sourceModel->element->siteSettings()->create(['siteId' => $secondSite->id]);
    $sourceModel->section->siteSettings()->create(['siteId' => $secondSite->id]);

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $this->field->id,
        'sourceId' => $sourceModel->id,
        'sourceSiteId' => null,
        'targetId' => $this->mergedEntry->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid()->toString(),
    ]);

    Queue::fake();

    app(MergeElementsAction::class)->handle($this->mergedEntry, $this->prevailingEntry);

    expect(DB::table(Table::RELATIONS)
        ->where('sourceId', $sourceModel->id)
        ->where('fieldId', $this->field->id)
        ->value('targetId'))->toBe($this->prevailingEntry->id);
});
