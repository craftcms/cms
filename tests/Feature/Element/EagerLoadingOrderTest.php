<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Database\Query\Expression;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());
    request()->attributes->set('isCpRequest', false);
    TemplateMode::set(TemplateMode::Site);

    $field = Field::factory()->create([
        'handle' => 'entriesField',
        'type' => Entries::class,
    ]);
    $fieldLayout = FieldLayout::factory()->forField($field)->create();
    $section = Section::factory()->create(['handle' => 'blog']);
    Sections::refreshSections();
    $entryType = EntryType::factory()->create(['fieldLayoutId' => $fieldLayout->id]);
    app(Fields::class)->invalidateCaches();
    app(Fields::class)->refreshFields();

    $this->sourceIds = [];
    $this->relatedIds = [];

    foreach (range(1, 2) as $i) {
        $source = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
        $source->element->update(['fieldLayoutId' => $fieldLayout->id]);
        $related = EntryModel::factory(2)->forSection($section)->forEntryType($entryType)->create()->pluck('id')->sort()->values()->all();

        $entry = entryQuery()->id($source->id)->firstOrFail();
        $entry->setFieldValue('entriesField', $related);
        Elements::saveElement($entry);

        $this->sourceIds[] = $source->id;
        $this->relatedIds[$source->id] = $related;
    }

    ElementCaches::invalidateAll();
});

/** @return int[] */
function eagerLoadRelatedIds(int $sourceId, array $criteria = []): array
{
    $entry = entryQuery()->id($sourceId)->with([['entriesField', $criteria]])->one();

    return $entry->getEagerLoadedElements('entriesField')->map(fn (ElementInterface $element) => $element->id)->all();
}

/**
 * @param  callable(EntryQuery): EntryQuery  $modifyQuery
 * @return int[]
 */
function eagerlyLoadRelatedIds(array $sourceIds, callable $modifyQuery, bool $one = false): array
{
    $entries = entryQuery()->id($sourceIds)->orderBy('elements.id')->all();
    expect($entries)->toHaveCount(2);

    /** @var EntryQuery $query */
    $query = $entries[0]->getFieldValue('entriesField');
    $query = $modifyQuery($query)->eagerly();
    $related = $one ? array_filter([$query->one()]) : $query->all();

    expect($query->wasEagerLoaded("eagerly:$query->eagerLoadHandle"))->toBeTrue();

    return array_map(fn (Entry $element) => $element->id, $related);
}

it('applies safe orderBy criteria to eager-loaded elements', function (mixed $orderBy) {
    $sourceId = $this->sourceIds[0];
    $relatedIds = $this->relatedIds[$sourceId];

    expect(eagerLoadRelatedIds($sourceId))->toBe($relatedIds)
        ->and(eagerLoadRelatedIds($sourceId, ['orderBy' => $orderBy]))->toBe(array_reverse($relatedIds))
        ->and(eagerLoadRelatedIds($sourceId, ['orderBy' => $orderBy, 'limit' => 1]))->toBe([$relatedIds[1]]);
})->with([
    'string' => 'elements.id desc',
    'column => SORT_DESC' => [['elements.id' => SORT_DESC]],
    'column/direction pairs' => [[['elements.id', 'desc']]],
    'expression' => fn () => new Expression('elements.id desc'),
]);

it('ignores unsafe orderBy criteria for eager-loaded elements', function (mixed $orderBy) {
    $sourceId = $this->sourceIds[0];

    expect(eagerLoadRelatedIds($sourceId, ['orderBy' => $orderBy]))->toBe($this->relatedIds[$sourceId]);
})->with([
    'subquery string' => 'elements.id desc, (select 1)',
    'subquery column' => [['(select 1)' => SORT_DESC]],
]);

it('only applies orderBy and inReverse criteria once to eager-loaded elements', function () {
    $sourceId = $this->sourceIds[0];
    $relatedIds = $this->relatedIds[$sourceId];

    expect(eagerLoadRelatedIds($sourceId, ['orderBy' => 'elements.id desc', 'inReverse' => true]))->toBe($relatedIds)
        ->and(eagerLoadRelatedIds($sourceId, ['inReverse' => true]))->toBe(array_reverse($relatedIds));
});

it('applies custom ordering, offset, and limit to elements eager-loaded via eagerly()', function (callable $modifyQuery, callable $expected, bool $one = false) {
    $relatedIds = $this->relatedIds[$this->sourceIds[0]];
    $lazyQuery = $modifyQuery(entryQuery()->id($this->sourceIds[0])->one()->getFieldValue('entriesField'));
    $lazyIds = $one ? array_filter([$lazyQuery->one()?->id]) : $lazyQuery->ids();

    expect(eagerlyLoadRelatedIds($this->sourceIds, $modifyQuery, $one))->toBe($expected($relatedIds))
        ->and($lazyIds)->toBe($expected($relatedIds));
})->with([
    'default order' => [fn (EntryQuery $query) => $query, fn (array $ids) => $ids],
    'appended orderBy' => [fn (EntryQuery $query) => $query->orderBy('elements.id', 'desc'), fn (array $ids) => $ids],
    'replaced orderBy' => [fn (EntryQuery $query) => $query->reorder()->orderBy('elements.id', 'desc'), array_reverse(...)],
    'replaced orderBy and limit' => [fn (EntryQuery $query) => $query->reorder()->orderBy('elements.id', 'desc')->limit(1), fn (array $ids) => [$ids[1]]],
    'replaced orderBy and offset' => [fn (EntryQuery $query) => $query->reorder()->orderBy('elements.id', 'desc')->offset(1), fn (array $ids) => [$ids[0]]],
    'limit' => [fn (EntryQuery $query) => $query->limit(1), fn (array $ids) => [$ids[0]]],
    'replaced orderBy and one()' => [fn (EntryQuery $query) => $query->reorder()->orderBy('elements.id', 'desc'), fn (array $ids) => [$ids[1]], true],
]);
