<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\Events\AfterRestoreElement;
use CraftCms\Cms\Element\Events\BeforeRestoreElement;
use CraftCms\Cms\Element\Exceptions\UnsupportedSiteException;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Models\Revision;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Element\Operations\ElementWrites;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Search\Search;
use CraftCms\Cms\Site\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('restores a trashed entry and updates restore side effects', function () {
    Event::fake([BeforeRestoreElement::class, AfterRestoreElement::class]);

    $entry = Entry::factory()->createElement();
    app(Elements::class)->deleteElement($entry);
    $entry = EntryElement::find()->id($entry->id)->trashed()->one();
    $entry->deletedWithOwner = true;
    $entry->trashed = true;

    DB::table(Table::ELEMENTS)->where('id', $entry->id)->update([
        'deletedWithOwner' => true,
    ]);

    $deletions = app(ElementDeletions::class);

    expect($deletions->restoreElements([$entry]))->toBeTrue();

    $elementRecord = ElementModel::withTrashed()->findOrFail($entry->id);

    expect($elementRecord->dateDeleted)->toBeNull()
        ->and($elementRecord->deletedWithOwner)->toBeNull()
        ->and($entry->trashed)->toBeFalse()
        ->and($entry->dateDeleted)->toBeNull()
        ->and($entry->deletedWithOwner)->toBeNull();

    Event::assertDispatched(fn (BeforeRestoreElement $event) => $event->element->id === $entry->id);
    Event::assertDispatched(fn (AfterRestoreElement $event) => $event->element->id === $entry->id);
});

it('returns false when an element vetoes restore in beforeRestore', function () {
    $action = restoreElementsService();
    $element = new TestRestoreElement(beforeRestoreResult: false);

    expect($action->restoreElements([$element]))->toBeFalse()
        ->and($element->afterRestoreCalls)->toBe(0);
});

it('returns false and rolls back when essential validation fails on the primary element', function () {
    $elementRecord = ElementModel::factory()->create([
        'type' => TestRestoreElement::class,
        'dateDeleted' => now(),
        'deletedWithOwner' => true,
    ]);

    $action = restoreElementsService();
    $element = new TestRestoreElement(validateResult: false);
    $element->id = $elementRecord->id;
    $element->siteId = 1;

    expect($action->restoreElements([$element]))->toBeFalse();

    expect(ElementModel::withTrashed()->findOrFail($elementRecord->id)->dateDeleted)->not->toBeNull();
});

it('throws when an element has no supported sites', function () {
    $action = restoreElementsService();
    $element = new TestRestoreElement(supportedSites: []);

    $action->restoreElements([$element]);
})->throws(UnsupportedSiteException::class, 'has no supported sites');

it('throws when an element is restored in an unsupported site', function () {
    $action = restoreElementsService();
    $site = Site::factory()->create(['handle' => 'unsupported-site']);
    $element = new TestRestoreElement(supportedSites: [['siteId' => $site->id]]);

    $action->restoreElements([$element]);
})->throws(UnsupportedSiteException::class, 'unsupported site');

it('throws and rolls back when another supported site fails essential validation', function () {
    $otherSite = Site::factory()->create(['handle' => 'rollback-site']);

    $elementRecord = ElementModel::factory()->create([
        'type' => TestRestoreElement::class,
        'dateDeleted' => now(),
    ]);

    $siteElement = new TestRestoreElement(validateResult: false);
    $siteElement->id = $elementRecord->id;
    $siteElement->siteId = $otherSite->id;

    $query = Mockery::mock(ElementQueryInterface::class);
    $query->shouldReceive('siteId')->once()->andReturnSelf();
    $query->shouldReceive('status')->once()->andReturnSelf();
    $query->shouldReceive('trashed')->once()->andReturnSelf();
    $query->shouldReceive('all')->once()->andReturn([$siteElement]);

    $action = restoreElementsService();
    $element = new TestRestoreElement(localizedQuery: $query, supportedSites: [
        ['siteId' => 1],
        ['siteId' => $otherSite->id],
    ]);
    $element->id = $elementRecord->id;
    $element->siteId = 1;

    expect(fn () => $action->restoreElements([$element]))->toThrow(Exception::class, "Element {$element->id} doesn't pass essential validation for site {$element->siteId}.");

    expect(ElementModel::withTrashed()->findOrFail($elementRecord->id)->dateDeleted)->not->toBeNull();
});

it('restores drafts and revisions, reindexes supported sites, and invalidates caches for each element', function () {
    Event::fake([BeforeRestoreElement::class, AfterRestoreElement::class]);

    $otherSite = Site::factory()->create(['handle' => 'localized-site']);

    $elementRecord = ElementModel::factory()->create([
        'type' => TestRestoreElement::class,
        'dateDeleted' => now(),
    ]);

    $draft = Draft::factory()->create([
        'canonicalId' => $elementRecord->id,
        'provisional' => false,
        'trackChanges' => true,
    ]);

    $revision = Revision::create([
        'canonicalId' => $elementRecord->id,
        'creatorId' => 1,
        'num' => 1,
        'notes' => null,
    ]);

    $draftElement = ElementModel::factory()->create([
        'type' => TestRestoreElement::class,
        'draftId' => $draft->id,
        'dateDeleted' => now(),
    ]);

    $revisionElement = ElementModel::factory()->create([
        'type' => TestRestoreElement::class,
        'revisionId' => $revision->id,
        'dateDeleted' => now(),
    ]);

    $siteElement = new TestRestoreElement;
    $siteElement->id = $elementRecord->id;
    $siteElement->siteId = $otherSite->id;

    $query = Mockery::mock(ElementQueryInterface::class);
    $query->shouldReceive('siteId')->once()->andReturnSelf();
    $query->shouldReceive('status')->once()->andReturnSelf();
    $query->shouldReceive('trashed')->once()->andReturnSelf();
    $query->shouldReceive('all')->once()->andReturn([$siteElement]);

    $indexed = [];
    $invalidated = [];

    $search = Mockery::mock(Search::class);
    $search->shouldReceive('indexElementAttributes')
        ->twice()
        ->andReturnUsing(function (ElementInterface $element, ?array $fieldHandles = null) use (&$indexed): bool {
            $indexed[] = [$element->id, $element->siteId];

            return true;
        });

    $elementCaches = Mockery::mock(ElementCaches::class);
    $elementCaches->shouldReceive('invalidateForElement')
        ->once()
        ->andReturnUsing(function (ElementInterface $element) use (&$invalidated): array {
            $invalidated[] = [$element->id, $element->siteId];

            return [];
        });

    $deletions = new ElementDeletions(
        Mockery::mock(Elements::class),
        Mockery::mock(ElementWrites::class),
        $elementCaches,
        $search,
    );

    $element = new TestRestoreElement(localizedQuery: $query, supportedSites: [
        ['siteId' => 1],
        ['siteId' => $otherSite->id],
    ]);
    $element->id = $elementRecord->id;
    $element->siteId = 1;
    $element->trashed = true;

    expect($deletions->restoreElements([$element]))->toBeTrue();

    expect(ElementModel::withTrashed()->findOrFail($draftElement->id)->dateDeleted)->toBeNull()
        ->and(ElementModel::withTrashed()->findOrFail($revisionElement->id)->dateDeleted)->toBeNull()
        ->and($indexed)->toBe([
            [$element->id, 1],
            [$siteElement->id, $otherSite->id],
        ])
        ->and($invalidated)->toBe([
            [$element->id, 1],
        ])
        ->and($element->afterRestoreCalls)->toBe(1);
});

function restoreElementsService(): ElementDeletions
{
    $search = Mockery::mock(Search::class);
    $search->shouldReceive('indexElementAttributes')->andReturn(true);

    $elementCaches = Mockery::mock(ElementCaches::class);
    $elementCaches->shouldReceive('invalidateForElement')->andReturn([]);

    return new ElementDeletions(
        Mockery::mock(Elements::class),
        Mockery::mock(ElementWrites::class),
        $elementCaches,
        $search,
    );
}

class TestRestoreElement extends Element
{
    public int $afterRestoreCalls = 0;

    public function __construct(
        private readonly bool $beforeRestoreResult = true,
        private readonly bool $validateResult = true,
        private readonly ?ElementQueryInterface $localizedQuery = null,
        private readonly array $supportedSites = [['siteId' => 1]],
        array $config = [],
    ) {
        parent::__construct($config);
        $this->siteId ??= 1;
        $this->id ??= 999;
    }

    #[Override]
    public static function displayName(): string
    {
        return 'Test Restore Element';
    }

    #[Override]
    public function beforeRestore(): bool
    {
        return $this->beforeRestoreResult;
    }

    #[Override]
    public function afterRestore(): void
    {
        $this->afterRestoreCalls++;
    }

    #[Override]
    public function validate($attributeNames = null, $clearErrors = true, bool $throw = false): bool
    {
        return $this->validateResult;
    }

    #[Override]
    public function getSupportedSites(): array
    {
        return $this->supportedSites;
    }

    #[Override]
    public function getLocalizedQuery(): ElementQueryInterface
    {
        if ($this->localizedQuery !== null) {
            return $this->localizedQuery;
        }

        $query = Mockery::mock(ElementQueryInterface::class);
        $query->shouldReceive('siteId')->andReturnSelf();
        $query->shouldReceive('status')->andReturnSelf();
        $query->shouldReceive('trashed')->andReturnSelf();
        $query->shouldReceive('all')->andReturn([]);

        return $query;
    }
}
