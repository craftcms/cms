<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Events\AfterDelete;
use CraftCms\Cms\Element\Events\BeforeDelete;
use CraftCms\Cms\Element\Models\Element as ElementModel;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('reports when no matching elements are found', function () {
    $this->artisan('craft:elements:delete-all-of-type', ['type' => Entry::class])
        ->expectsOutputToContain('No entries found.')
        ->assertSuccessful();
});

it('supports a dry run without deleting rows', function () {
    $entry = EntryModel::factory()->createElement();

    insertSearchIndexRow($entry->id);

    $this->artisan('elements/delete-all-of-type', ['type' => Entry::class, '--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN] 1 entry deleted.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id))->not()->toBeNull();
    expect(searchIndexExistsFor($entry->id))->toBeTrue();
});

it('prompts for a type when the argument is missing', function () {
    $entry = EntryModel::factory()->createElement();

    $this->artisan('craft:elements:delete-all-of-type --dry-run')
        ->expectsQuestion('Select an element type', 'Entries (CraftCms\\Cms\\Entry\\Elements\\Entry)')
        ->expectsOutputToContain('[DRY RUN] 1 entry deleted.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id))->not()->toBeNull();
});

it('deletes matching elements and their search indexes without dispatching delete events', function () {
    Event::fake([BeforeDelete::class, AfterDelete::class]);

    $entry = EntryModel::factory()->createElement();

    insertSearchIndexRow($entry->id);

    $this->artisan('craft:elements:delete-all-of-type', ['type' => Entry::class, '--no-interaction' => true])
        ->expectsOutputToContain('1 entry deleted.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($entry->id))->toBeNull();
    expect(searchIndexExistsFor($entry->id))->toBeFalse();

    Event::assertNotDispatched(BeforeDelete::class);
    Event::assertNotDispatched(AfterDelete::class);
});

it('excludes single section entries from bulk deletion', function () {
    $channelSection = Section::factory()->create([
        'type' => SectionType::Channel,
    ]);
    $singleSection = Section::factory()->create([
        'type' => SectionType::Single,
    ]);

    $channelEntry = EntryModel::factory()->forSection($channelSection)->createElement();
    $singleEntry = EntryModel::factory()->forSection($singleSection)->createElement();

    insertSearchIndexRow($channelEntry->id);
    insertSearchIndexRow($singleEntry->id);

    $this->artisan('craft:elements:delete-all-of-type', ['type' => Entry::class])
        ->expectsConfirmation('Continue?', 'yes')
        ->expectsOutputToContain('1 entry deleted.')
        ->assertSuccessful();

    expect(ElementModel::withTrashed()->find($channelEntry->id))->toBeNull();
    expect(searchIndexExistsFor($channelEntry->id))->toBeFalse();
    expect(ElementModel::withTrashed()->find($singleEntry->id))->not()->toBeNull();
    expect(searchIndexExistsFor($singleEntry->id))->toBeTrue();
});

function insertSearchIndexRow(int $elementId): void
{
    $row = [
        'elementId' => $elementId,
        'attribute' => 'title',
        'fieldId' => 0,
        'siteId' => Sites::getCurrentSite()->id,
        'keywords' => 'keywords',
    ];

    if (DB::connection()->isPgsql()) {
        $row['keywords_vector'] = 'keywords';
    }

    DB::table(Table::SEARCHINDEX)->insert($row);
}

function searchIndexExistsFor(int $elementId): bool
{
    return DB::table(Table::SEARCHINDEX)
        ->where('elementId', $elementId)
        ->exists();
}
