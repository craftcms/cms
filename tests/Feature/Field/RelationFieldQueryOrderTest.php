<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

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

    $relatedIds = [];
    foreach (['Banana', 'Apple', 'Cherry'] as $title) {
        $related = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
        $entry = entryQuery()->id($related->id)->firstOrFail();
        $entry->title = $title;
        Elements::saveElement($entry);
        $relatedIds[] = $related->id;
    }

    $source = EntryModel::factory()->forSection($section)->forEntryType($entryType)->create();
    $source->element->update(['fieldLayoutId' => $fieldLayout->id]);
    $entry = entryQuery()->id($source->id)->firstOrFail();
    $entry->setFieldValue('entriesField', $relatedIds);
    Elements::saveElement($entry);

    $this->sourceId = $source->id;
});

/** @return string[] */
function relatedTitles(int $sourceId, callable $modifyQuery): array
{
    /** @var EntryQuery $query */
    $query = entryQuery()->id($sourceId)->firstOrFail()->getFieldValue('entriesField');

    return array_map(fn ($entry) => $entry->title, $modifyQuery($query)->all());
}

it('returns related elements in the order they were related', function () {
    expect(relatedTitles($this->sourceId, fn (EntryQuery $query) => $query))->toBe(['Banana', 'Apple', 'Cherry']);
});

it('replaces the related order with a custom order', function () {
    expect(relatedTitles($this->sourceId, fn (EntryQuery $query) => $query->orderBy('title')))
        ->toBe(['Apple', 'Banana', 'Cherry'])
        ->and(relatedTitles($this->sourceId, fn (EntryQuery $query) => $query->orderBy('title', 'desc')))
        ->toBe(['Cherry', 'Banana', 'Apple']);
});

it('reverses the related order', function () {
    expect(relatedTitles($this->sourceId, fn (EntryQuery $query) => $query->inReverse()))
        ->toBe(['Cherry', 'Apple', 'Banana']);
});

it('reverses a custom order', function () {
    expect(relatedTitles($this->sourceId, fn (EntryQuery $query) => $query->orderBy('title')->inReverse()))
        ->toBe(['Cherry', 'Banana', 'Apple']);
});
