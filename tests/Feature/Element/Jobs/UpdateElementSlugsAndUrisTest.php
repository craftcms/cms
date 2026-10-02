<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Jobs\UpdateElementSlugsAndUris;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use Illuminate\Support\Facades\DB;

it('can be instantiated with element type only', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    expect($job->elementType)->toBe(EntryElement::class)
        ->and($job->elementId)->toBeNull()
        ->and($job->siteId)->toBeNull()
        ->and($job->updateOtherSites)->toBeTrue()
        ->and($job->updateDescendants)->toBeTrue();
});

it('provides a description', function () {
    $job = new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('slugs')
        ->and($description)->toContain('URIs');
});

it('updates the URIs of all entries', function () {
    [$section, $entries] = createEntriesWithUriFormat(2);

    $section->siteSettings()->update(['uriFormat' => 'news/{slug}']);
    Sections::refreshSections();

    new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
    )->handle();

    foreach ($entries as $entry) {
        expect(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->value('uri'))
            ->toBe("news/$entry->slug");
    }
});

it('only updates the URI of the given element', function () {
    [$section, [$targetEntry, $otherEntry]] = createEntriesWithUriFormat(2);

    $section->siteSettings()->update(['uriFormat' => 'news/{slug}']);
    Sections::refreshSections();

    new UpdateElementSlugsAndUris(
        elementType: EntryElement::class,
        elementId: $targetEntry->id,
        siteId: $targetEntry->siteId,
    )->handle();

    expect(DB::table(Table::ELEMENTS_SITES)->where('elementId', $targetEntry->id)->value('uri'))
        ->toBe("news/$targetEntry->slug")
        ->and(DB::table(Table::ELEMENTS_SITES)->where('elementId', $otherEntry->id)->value('uri'))
        ->toBe("blog/$otherEntry->slug");
});

/**
 * @return array{0: Section, 1: list<EntryElement>}
 */
function createEntriesWithUriFormat(int $count): array
{
    $section = Section::factory()
        ->withEntryTypes($entryType = EntryType::factory()->create())
        ->create();
    $section->siteSettings()->update([
        'hasUrls' => true,
        'uriFormat' => 'blog/{slug}',
    ]);
    Sections::refreshSections();

    $entries = [];

    for ($i = 0; $i < $count; $i++) {
        $entry = Entry::factory()
            ->forSection($section)
            ->forEntryType($entryType)
            ->createElement();
        Elements::saveElement($entry);
        $entries[] = $entry;

        expect(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->value('uri'))
            ->toBe("blog/$entry->slug");
    }

    return [$section, $entries];
}
