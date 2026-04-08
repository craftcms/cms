<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Events\AfterDeleteForSite;
use CraftCms\Cms\Element\Events\BeforeDeleteForSite;
use CraftCms\Cms\Element\Operations\ElementDeletions;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    $this->deletions = app(ElementDeletions::class);
});

it('does nothing when no elements are provided', function () {
    $elementSiteCount = DB::table(Table::ELEMENTS_SITES)->count();

    $this->deletions->deleteElementsForSite([]);

    expect(DB::table(Table::ELEMENTS_SITES)->count())->toBe($elementSiteCount);
});

it('requires all elements to have the same type and site id', function () {
    $primarySite = Site::firstOrFail();
    $secondSite = Site::factory()->create();

    Sites::refreshSites();

    $firstEntry = EntryModel::factory()->createElement();
    $secondEntry = EntryModel::factory()->createElement();
    $secondEntry->siteId = $secondSite->id;

    expect(fn () => $this->deletions->deleteElementsForSite([$firstEntry, $secondEntry]))
        ->toThrow(InvalidArgumentException::class, 'All elements must have the same type and site ID.');

    expect($primarySite->id)->not()->toBe($secondSite->id);
});

it('hard deletes single-site elements', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Single-site entry']);

    $this->deletions->deleteElementsForSite([$entry]);

    expect(Entry::find()->status(null)->siteId($entry->siteId)->id($entry->id)->exists())->toBeFalse()
        ->and(DB::table(Table::ELEMENTS)->where('id', $entry->id)->exists())->toBeFalse()
        ->and(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->where('siteId', $entry->siteId)->exists())->toBeFalse();
});

it('deletes only the requested site for multi-site elements and dispatches events', function () {
    Event::fake([
        BeforeDeleteForSite::class,
        AfterDeleteForSite::class,
    ]);

    [$entry, $secondarySite] = createMultiSiteEntry();

    $siteEntry = entryQuery()
        ->id($entry->id)
        ->siteId($secondarySite->id)
        ->status(null)
        ->one();

    $this->deletions->deleteElementsForSite([$siteEntry]);

    expect(DB::table(Table::ELEMENTS)->where('id', $entry->id)->value('dateDeleted'))->toBeNull()
        ->and(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->where('siteId', $secondarySite->id)->exists())->toBeFalse()
        ->and(DB::table(Table::ELEMENTS_SITES)->where('elementId', $entry->id)->where('siteId', $entry->siteId)->exists())->toBeTrue()
        ->and(entryQuery()->id($entry->id)->siteId($entry->siteId)->status(null)->exists())->toBeTrue()
        ->and(entryQuery()->id($entry->id)->siteId($secondarySite->id)->status(null)->exists())->toBeFalse();

    Event::assertDispatched(fn (BeforeDeleteForSite $event): bool => $event->element->id === $entry->id && $event->element->siteId === $secondarySite->id);
    Event::assertDispatched(fn (AfterDeleteForSite $event): bool => $event->element->id === $entry->id && $event->element->siteId === $secondarySite->id);
});

it('removes localized relations when deleting an element for a site', function () {
    $field = Field::factory()->create([
        'handle' => 'relatedEntries',
        'type' => Entries::class,
    ]);

    $fieldLayout = FieldLayout::factory()->forField($field)->create();

    [$entry, $secondarySite, $section, $entryType] = createMultiSiteEntry(fieldLayoutId: $fieldLayout->id);

    $entry->fieldLayoutId = $fieldLayout->id;
    Elements::saveElement($entry);

    $secondaryEntry = entryQuery()
        ->id($entry->id)
        ->siteId($secondarySite->id)
        ->status(null)
        ->one();

    $secondaryEntry->fieldLayoutId = $fieldLayout->id;

    $targetEntry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->create();

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $field->id,
        'sourceId' => $secondaryEntry->id,
        'sourceSiteId' => $secondarySite->id,
        'targetId' => $targetEntry->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    DB::table(Table::RELATIONS)->insert([
        'fieldId' => $field->id,
        'sourceId' => $secondaryEntry->id,
        'sourceSiteId' => null,
        'targetId' => $targetEntry->id,
        'sortOrder' => 1,
        'dateCreated' => now(),
        'dateUpdated' => now(),
        'uid' => Str::uuid(),
    ]);

    $this->deletions->deleteElementsForSite([$secondaryEntry]);

    $remainingRelations = DB::table(Table::RELATIONS)
        ->where('sourceId', $entry->id)
        ->orderBy('id')
        ->get();

    expect($remainingRelations)->toHaveCount(1)
        ->and($remainingRelations[0]->sourceSiteId)->not()->toBe($secondarySite->id);
});

function createMultiSiteEntry(?int $fieldLayoutId = null): array
{
    $secondarySite = Site::factory()->create([
        'handle' => 'secondary',
        'name' => 'Secondary Site',
    ]);

    Sites::refreshSites();

    $section = Section::factory()->withEntryTypes(
        $entryType = EntryType::factory()->create([
            'fieldLayoutId' => $fieldLayoutId,
        ])
    )->create([
        'propagationMethod' => PropagationMethod::Custom,
    ]);

    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondarySite->id,
        'hasUrls' => true,
        'dateCreated' => $section->dateCreated,
        'dateUpdated' => $section->dateUpdated,
    ]);

    app(Fields::class)->invalidateCaches();
    app(Fields::class)->refreshFields();

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Multi-site entry']);

    if ($fieldLayoutId !== null) {
        $entry->fieldLayoutId = $fieldLayoutId;
        Elements::saveElement($entry);
    }

    $entry->setEnabledForSite([
        $entry->siteId => true,
        $secondarySite->id => true,
    ]);
    Elements::saveElement($entry);

    return [$entry, $secondarySite, $section, $entryType];
}
