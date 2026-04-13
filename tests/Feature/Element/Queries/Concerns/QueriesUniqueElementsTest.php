<?php

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\ContentBlock as ContentBlockField;
use CraftCms\Cms\Field\Elements\ContentBlock as ContentBlockElement;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;

test('unique', function () {
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

    expect(entryQuery()->site('*')->count())->toBe(2);
    expect(entryQuery()->site('*')->unique()->count())->toBe(1);
    expect(entryQuery()->site('*')->unique()->first()->siteId)->toBe($site1->id);

    Sites::setCurrentSite($site2->handle);

    expect(entryQuery()->site('*')->unique()->first()->siteId)->toBe($site2->id);

    expect(entryQuery()->site('*')->preferSites([$site2->id, $site1->id])->unique()->first()->siteId)->toBe($site2->id);
    expect(entryQuery()->site('*')->preferSites([$site2->handle, $site1->handle])->unique()->first()->siteId)->toBe($site2->id);
});

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
