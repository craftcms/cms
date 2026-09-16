<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;

test('unique', function (bool $generic) {
    $query = fn () => $generic ? new ElementQuery(EntryElement::class) : entryQuery();
    $site1 = Site::firstOrFail();
    $site2 = Site::factory()->create();

    Sites::refreshSites();

    $entry = Entry::factory()->create();
    $entry->element->siteSettings()->create([
        'siteId' => $site2->id,
    ]);
    $entry->section->siteSettings()->create([
        'siteId' => $site2->id,
    ]);

    $otherEntry = Entry::factory()->create();
    $otherEntry->element->siteSettings()->create(['siteId' => $site2->id]);

    $fallbackEntry = Entry::factory()->create();
    $fallbackEntry->element->siteSettings()->update(['siteId' => $site2->id]);

    expect($query()->site('*')->count())->toBe(5);
    expect($query()->site('*')->unique()->count())->toBe(3);
    expect($query()->site('*')->unique()->pluck('elements.id')->all())
        ->toEqualCanonicalizing([$entry->id, $otherEntry->id, $fallbackEntry->id]);
    expect($query()->site('*')->unique()->pluck('elements_sites.siteId', 'elements.id')->sortKeys()->all())
        ->toBe([$entry->id => $site1->id, $otherEntry->id => $site1->id, $fallbackEntry->id => $site2->id]);

    Sites::setCurrentSite($site2->handle);

    $preferredSites = [$entry->id => $site2->id, $otherEntry->id => $site2->id, $fallbackEntry->id => $site2->id];

    expect($query()->site('*')->unique()->pluck('elements_sites.siteId', 'elements.id')->sortKeys()->all())->toBe($preferredSites);
    expect($query()->site('*')->preferSites([$site2->id, $site1->id])->unique()->pluck('elements_sites.siteId', 'elements.id')->sortKeys()->all())->toBe($preferredSites);
    expect($query()->site('*')->preferSites([$site2->handle, $site1->handle])->unique()->pluck('elements_sites.siteId', 'elements.id')->sortKeys()->all())->toBe($preferredSites);
    expect($query()->site('*')->unique()->orderBy('elements.id')->offset(1)->limit(1)->pluck('elements.id')->all())->toBe([$otherEntry->id]);

    $entry->element->siteSettings()->where('siteId', $site1->id)->update(['title' => 'Only matching site']);

    expect($query()->site('*')->unique()->where('elements_sites.title', 'Only matching site')->pluck('elements_sites.siteId', 'elements.id')->all())
        ->toBe([$entry->id => $site1->id]);
})->with(['entry table' => false, 'element table' => true]);

test('unique still deduplicates when siteId changes after the site filter is applied', function () {
    $site1 = Site::firstOrFail();
    $site2 = Site::factory()->create();

    $field = Field::factory()->create([
        'type' => ContentBlockField::class,
    ]);

    $owner = Entry::factory()->create();
    $owner->element->siteSettings()->create([
        'siteId' => $site2->id,
    ]);
    $owner->section->siteSettings()->create([
        'siteId' => $site2->id,
    ]);

    $contentBlock = ElementModel::factory()->create([
        'type' => ContentBlockElement::class,
    ]);
    $contentBlock->siteSettings()->create([
        'siteId' => $site2->id,
    ]);

    DB::table(Table::CONTENTBLOCKS)->insert([
        'id' => $contentBlock->id,
        'fieldId' => $field->id,
        'primaryOwnerId' => $owner->id,
    ]);

    DB::table(Table::ELEMENTS_OWNERS)->insert([
        'elementId' => $contentBlock->id,
        'ownerId' => $owner->id,
        'sortOrder' => 1,
    ]);

    $contentBlockQuery = fn () => tap(
        ContentBlockElement::find()
            ->fieldId($field->id)
            ->siteId([$site1->id, $site2->id])
            ->preferSites([$site1->id])
            ->status(null),
        fn (ContentBlockQuery $query) => $query->beforeQuery(
            fn (ContentBlockQuery $query) => $query->owner(entryQuery()->id($owner->id)->one())
        ),
    );

    expect($contentBlockQuery()->count())->toBe(2);
    expect($contentBlockQuery()->unique()->count())->toBe(1);
    expect($contentBlockQuery()->unique()->first()->siteId)->toBe($site1->id);
});
