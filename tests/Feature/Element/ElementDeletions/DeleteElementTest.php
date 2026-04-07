<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\BulkOp\BulkOps;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Events\AfterDelete;
use CraftCms\Cms\Element\Events\AfterDeleteElement;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Element\Events\BeforeDeleteElement;
use CraftCms\Cms\Element\Events\InvalidateElementCaches;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->deletions = app(ElementDeletions::class);
    $this->bulkOps = app(BulkOps::class);
    $this->bulkOpConnection = DB::connection('db2');
    $this->drafts = app(Drafts::class);
    $this->revisions = app(Revisions::class);

    actingAs(User::findOne());
});

function insertSearchIndexRowForSite(int $elementId, int $siteId): void
{
    $row = [
        'elementId' => $elementId,
        'attribute' => 'title',
        'fieldId' => 0,
        'siteId' => $siteId,
        'keywords' => 'keywords',
    ];

    if (DB::connection()->isPgsql()) {
        $row['keywords_vector'] = 'keywords';
    }

    DB::table(Table::SEARCHINDEX)->insertOrIgnore($row);
}

it('returns false when beforeDelete vetoes the delete', function () {
    $entry = EntryModel::factory()->createElement();

    insertSearchIndexRowForSite($entry->id, $entry->siteId);

    Event::fake([
        BeforeDeleteElement::class,
        AfterDeleteElement::class,
        InvalidateElementCaches::class,
    ]);

    Event::listen(BeforeDelete::class, function (BeforeDelete $event) use ($entry) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        $event->isValid = false;
    });

    expect($this->deletions->deleteElement($entry))->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->toBeNull()
        ->and(DB::table(Table::SEARCHINDEX)->where('elementId', $entry->id)->exists())->toBeTrue();

    Event::assertDispatched(fn (BeforeDeleteElement $event): bool => $event->element->id === $entry->id && $event->hardDelete === false);
    Event::assertNotDispatched(AfterDeleteElement::class);
    Event::assertNotDispatched(InvalidateElementCaches::class);
});

it('soft deletes an element, cascades drafts and revisions, and tracks it in the current bulk op', function () {
    $entry = EntryModel::factory()->createElement();
    $entry->deletedWithOwner = true;

    $draft = app(Drafts::class)->createDraft($entry, name: 'Draft');

    /** @var Entry $revision */
    $revision = Elements::getElementById(
        app(Revisions::class)->createRevision($entry, notes: 'Revision notes'),
    );

    insertSearchIndexRowForSite($entry->id, $entry->siteId);

    Event::fake([
        BeforeDelete::class,
        AfterDelete::class,
        BeforeDeleteElement::class,
        AfterDeleteElement::class,
        InvalidateElementCaches::class,
    ]);

    $key = $this->bulkOps->start();

    try {
        expect($this->deletions->deleteElement($entry))->toBeTrue()
            ->and(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->not()->toBeNull()
            ->and((bool) DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('deletedWithOwner'))->toBeTrue()
            ->and(DB::table(Table::SEARCHINDEX)->where('elementId', $entry->id)->exists())->toBeTrue()
            ->and(DB::table(Table::ELEMENTS)->where('id', $draft->id)->value('dateDeleted'))->not()->toBeNull()
            ->and(DB::table(Table::ELEMENTS)->where('id', $revision->id)->value('dateDeleted'))->not()->toBeNull()
            ->and(DB::table(Table::DRAFTS)->where('id', $draft->draftId)->exists())->toBeTrue()
            ->and(DB::table(Table::REVISIONS)->where('id', $revision->revisionId)->exists())->toBeTrue()
            ->and($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
                ->where('key', $key)
                ->where('elementId', $entry->id)
                ->count())->toBe(1);

        Event::assertDispatched(fn (BeforeDeleteElement $event): bool => $event->element->id === $entry->id && $event->hardDelete === false);
        Event::assertDispatched(fn (AfterDeleteElement $event): bool => $event->element->id === $entry->id);
        Event::assertDispatched(fn (BeforeDelete $event): bool => $event->element->id === $entry->id);
        Event::assertDispatched(fn (AfterDelete $event): bool => $event->element->id === $entry->id);
        Event::assertDispatched(fn (InvalidateElementCaches $event): bool => $event->element?->id === $entry->id);
    } finally {
        $this->bulkOps->end($key);
    }
});

it('hard deletes an element and removes its search indexes without tracking it', function () {
    $entry = EntryModel::factory()->createElement();

    insertSearchIndexRowForSite($entry->id, $entry->siteId);

    Event::fake([
        BeforeDelete::class,
        AfterDelete::class,
        BeforeDeleteElement::class,
        AfterDeleteElement::class,
        InvalidateElementCaches::class,
    ]);

    $key = $this->bulkOps->start();

    try {
        expect($this->deletions->deleteElement($entry, true))->toBeTrue()
            ->and(DB::table(Table::ELEMENTS)->where('id', $entry->id)->exists())->toBeFalse()
            ->and(DB::table(Table::SEARCHINDEX)->where('elementId', $entry->id)->exists())->toBeFalse()
            ->and($this->bulkOpConnection->table(Table::ELEMENTS_BULKOPS)
                ->where('key', $key)
                ->where('elementId', $entry->id)
                ->count())->toBe(0);

        Event::assertDispatched(fn (BeforeDeleteElement $event): bool => $event->element->id === $entry->id && $event->hardDelete === true);
        Event::assertDispatched(fn (AfterDeleteElement $event): bool => $event->element->id === $entry->id);
        Event::assertDispatched(fn (BeforeDelete $event): bool => $event->element->id === $entry->id);
        Event::assertDispatched(fn (AfterDelete $event): bool => $event->element->id === $entry->id);
        Event::assertDispatched(fn (InvalidateElementCaches $event): bool => $event->element?->id === $entry->id);
    } finally {
        $this->bulkOps->end($key);
    }
});

it('allows BeforeDeleteElement to force hard deleting derivative elements', function (string $type, string $table) {
    $canonical = EntryModel::factory()->createElement();

    if ($type === 'draft') {
        $derivative = app(Drafts::class)->createDraft($canonical, name: 'Draft');
        $metadataId = $derivative->draftId;
    } else {
        /** @var Entry $derivative */
        $derivative = Elements::getElementById(
            app(Revisions::class)->createRevision($canonical, notes: 'Revision notes'),
        );
        $metadataId = $derivative->revisionId;
    }

    insertSearchIndexRowForSite($derivative->id, $derivative->siteId);

    $receivedHardDelete = null;
    $afterDeleteElementDispatched = false;

    Event::listen(BeforeDeleteElement::class, function (BeforeDeleteElement $event) use ($derivative, &$receivedHardDelete) {
        if ($event->element->id !== $derivative->id) {
            return;
        }

        $receivedHardDelete = $event->hardDelete;
        $event->hardDelete = true;
    });

    Event::listen(AfterDeleteElement::class, function (AfterDeleteElement $event) use ($derivative, &$afterDeleteElementDispatched) {
        if ($event->element->id !== $derivative->id) {
            return;
        }

        $afterDeleteElementDispatched = true;
    });

    expect($this->deletions->deleteElement($derivative))->toBeTrue()
        ->and($receivedHardDelete)->toBeFalse()
        ->and($derivative->hardDelete)->toBeTrue()
        ->and($derivative->dateDeleted)->not()->toBeNull()
        ->and(DB::table(Table::ELEMENTS)->where('id', $derivative->id)->exists())->toBeFalse()
        ->and(DB::table($table)->where('id', $metadataId)->exists())->toBeFalse()
        ->and(DB::table(Table::SEARCHINDEX)->where('elementId', $derivative->id)->exists())->toBeFalse()
        ->and($afterDeleteElementDispatched)->toBeTrue();
})->with([
    'draft' => ['draft', Table::DRAFTS],
    'revision' => ['revision', Table::REVISIONS],
]);

it('moves structure children up before removing the deleted element node', function () {
    [
        'structure' => $structure,
        'root' => $root,
        'children' => [$child1, $child2],
        'nested' => [$grandChild],
    ] = createStructureHierarchy();

    expect(StructureElement::where('structureId', $structure->id)->count())->toBe(4);

    $this->deletions->deleteElement($child1);

    $rootNode = StructureElement::where('structureId', $structure->id)
        ->where('elementId', $root->id)
        ->firstOrFail();

    $grandChildNode = StructureElement::where('structureId', $structure->id)
        ->where('elementId', $grandChild->id)
        ->firstOrFail();

    $childElementIds = $rootNode->children(1)
        ->orderBy('lft')
        ->pluck('elementId')
        ->all();

    expect(StructureElement::where('structureId', $structure->id)->count())->toBe(3)
        ->and(StructureElement::where('structureId', $structure->id)->where('elementId', $child1->id)->exists())->toBeFalse()
        ->and($grandChildNode->level)->toBe(1)
        ->and($grandChildNode->parents(1)->first()?->elementId)->toBe($root->id)
        ->and($childElementIds)->toBe([$grandChild->id, $child2->id]);
});

it('rolls back the delete when afterDelete throws', function () {
    $entry = EntryModel::factory()->createElement();

    insertSearchIndexRowForSite($entry->id, $entry->siteId);

    $beforeDeleteElementDispatched = false;
    $afterDeleteElementDispatched = false;

    Event::listen(BeforeDeleteElement::class, function (BeforeDeleteElement $event) use ($entry, &$beforeDeleteElementDispatched) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        $beforeDeleteElementDispatched = true;
    });

    Event::listen(AfterDelete::class, function (AfterDelete $event) use ($entry) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        throw new RuntimeException('delete failed');
    });

    Event::listen(AfterDeleteElement::class, function (AfterDeleteElement $event) use ($entry, &$afterDeleteElementDispatched) {
        if ($event->element->id !== $entry->id) {
            return;
        }

        $afterDeleteElementDispatched = true;
    });

    expect(fn () => $this->deletions->deleteElement($entry))
        ->toThrow(RuntimeException::class, 'delete failed');

    expect($beforeDeleteElementDispatched)->toBeTrue()
        ->and($afterDeleteElementDispatched)->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->toBeNull()
        ->and(DB::table(Table::SEARCHINDEX)->where('elementId', $entry->id)->exists())->toBeTrue();
});
