<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Jobs\ApplyNewPropagationMethod;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;

it('initializes duplicatedElementIds as empty array', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    expect($job->duplicatedElementIds)->toBe([]);
});

it('provides a description', function () {
    $job = new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
    );

    $description = $job->getDescription();

    expect($description)->toContain('propagation method');
});

it('duplicates entries into sites they no longer propagate to', function () {
    $primarySite = Sites::getPrimarySite();
    $secondarySite = Site::factory()->create();
    Sites::refreshSites();

    $section = Section::factory()
        ->withEntryTypes($entryType = EntryType::factory()->create())
        ->create([
            'propagationMethod' => PropagationMethod::All,
            'enableVersioning' => false,
        ]);

    SectionSiteSettings::factory()->create([
        'sectionId' => $section->id,
        'siteId' => $secondarySite->id,
    ]);
    Sections::refreshSections();

    $entry = Entry::factory()
        ->forSection($section)
        ->forEntryType($entryType)
        ->createElement(['title' => 'Primary title']);
    Elements::saveElement($entry);

    $secondaryEntry = EntryElement::find()->id($entry->id)->siteId($secondarySite->id)->one();
    $secondaryEntry->title = 'Secondary title';
    Elements::saveElement($secondaryEntry);

    $section->update(['propagationMethod' => PropagationMethod::None]);
    Sections::refreshSections();

    new ApplyNewPropagationMethod(
        elementType: EntryElement::class,
        criteria: ['sectionId' => $section->id],
    )->handle();

    $siteRows = DB::table(Table::ELEMENTS_SITES)
        ->join(Table::ENTRIES, 'entries.id', '=', 'elements_sites.elementId')
        ->join(Table::ELEMENTS, 'elements.id', '=', 'elements_sites.elementId')
        ->whereNull('elements.dateDeleted')
        ->where('entries.sectionId', $section->id)
        ->orderBy('elements_sites.siteId')
        ->get(['elements_sites.elementId', 'elements_sites.siteId', 'elements_sites.title']);

    expect($siteRows)->toHaveCount(2)
        ->and($siteRows[0]->elementId)->toBe($entry->id)
        ->and($siteRows[0]->siteId)->toBe($primarySite->id)
        ->and($siteRows[0]->title)->toBe('Primary title')
        ->and($siteRows[1]->elementId)->not->toBe($entry->id)
        ->and($siteRows[1]->siteId)->toBe($secondarySite->id)
        ->and($siteRows[1]->title)->toBe('Secondary title');
});
