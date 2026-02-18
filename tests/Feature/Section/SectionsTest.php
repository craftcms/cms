<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Events\ApplyingSectionDelete;
use CraftCms\Cms\Section\Events\DeletingSection;
use CraftCms\Cms\Section\Events\SavingSection;
use CraftCms\Cms\Section\Events\SectionDeleted;
use CraftCms\Cms\Section\Events\SectionSaved;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sections as SectionsFacade;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->sections = app(Sections::class);
});

it('is a singleton', function () {
    expect(SectionsFacade::getFacadeRoot())->toBe(app(Sections::class));
    expect($this->sections)->toBe(app(Sections::class));
});

it('can get all sections', function () {
    expect($this->sections->getAllSections())->toBeEmpty();
    expect($this->sections->getAllSectionIds())->toBeEmpty();
    expect($this->sections->getTotalSections())->toBe(0);

    $section = Section::factory()->create();
    $this->sections->refreshSections();

    expect($this->sections->getAllSections()->pluck('id'))->toContain($section->id);
    expect($this->sections->getAllSectionIds())->toContain($section->id);
    expect($this->sections->getTotalSections())->toBe(1);
});

it('can get editable sections', function () {
    expect($this->sections->getEditableSections())->toBeEmpty();
    expect($this->sections->getEditableSectionIds())->toBeEmpty();
    expect($this->sections->getTotalEditableSections())->toBe(0);

    $section = Section::factory()->create();
    $this->sections->refreshSections();

    expect($this->sections->getEditableSections()->pluck('id'))->toContain($section->id);
    expect($this->sections->getEditableSectionIds())->toContain($section->id);
    expect($this->sections->getTotalEditableSections())->toBe(1);
});

it('can get sections by type', function () {
    expect($this->sections->getSectionsByType(SectionType::Single))->toBeEmpty();

    $section = Section::factory()->create([
        'type' => SectionType::Single,
    ]);
    $this->sections->refreshSections();

    expect($this->sections->getSectionsByType(SectionType::Single)->pluck('id'))->toContain($section->id);
    expect($this->sections->getSectionsByType(SectionType::Channel))->toBeEmpty();
});

it('can get a section by id', function () {
    $section = Section::factory()->create();
    $this->sections->refreshSections();

    expect($this->sections->getSectionById($section->id))->toBeInstanceOf(SectionData::class);
    expect($this->sections->getSectionById(999))->toBeNull();
});

it('can get a section by uid', function () {
    $section = Section::factory()->create();
    $this->sections->refreshSections();

    expect($this->sections->getSectionByUid($section->uid))->toBeInstanceOf(SectionData::class);
    expect($this->sections->getSectionByUid(Str::uuid()->toString()))->toBeNull();
});

it('can get a section by handle', function () {
    $section = Section::factory()->create();
    $this->sections->refreshSections();

    expect($this->sections->getSectionByHandle($section->handle))->toBeInstanceOf(SectionData::class);
    expect($this->sections->getSectionByHandle('some-other-handle'))->toBeNull();
});

it('can get a section\'s site settings', function () {
    $siteSettings = SectionSiteSettings::factory()->create();
    $this->sections->refreshSections();

    $sectionSiteSettings = $this->sections->getSectionSiteSettings($siteSettings->sectionId);

    expect($sectionSiteSettings)->not()->toBeEmpty();
    expect(Arr::last($sectionSiteSettings))->toBeInstanceOf(SectionSiteSettingsData::class);
    expect(Arr::last($sectionSiteSettings)->id)->toBe($siteSettings->id);
    expect(Arr::last($sectionSiteSettings)->hasUrls)->toBe($siteSettings->hasUrls);
});

it('can save a section', function () {
    Event::fake([
        SavingSection::class,
        SectionSaved::class,
    ]);

    Event::listen(SavingSection::class, fn () => null);
    Event::listen(SectionSaved::class, fn () => null);

    expect(Section::count())->toBe(0);

    $this->sections->saveSection(new SectionData([
        'name' => 'Test section',
        'handle' => 'testSection',
        'type' => SectionType::Channel,
        'entryTypes' => [EntryType::factory()->create()->id],
        'siteSettings' => [
            new SectionSiteSettingsData([
                'siteId' => Sites::getCurrentSite()->id,
            ]),
        ],
    ]));

    expect(Section::count())->toBe(1);

    Event::assertDispatchedOnce(SavingSection::class);
    Event::assertDispatchedOnce(SectionSaved::class);
});

it('can delete a section by id', function () {
    Event::fake([
        DeletingSection::class,
        ApplyingSectionDelete::class,
        SectionDeleted::class,
    ]);

    Event::listen(DeletingSection::class, fn () => null);
    Event::listen(ApplyingSectionDelete::class, fn () => null);
    Event::listen(SectionDeleted::class, fn () => null);

    $section = Section::factory()->create();
    $this->sections->refreshSections();
    ProjectConfig::rebuild();

    expect(Section::count())->toBe(1);

    expect($this->sections->deleteSectionById($section->id))->toBeTrue();

    expect(Section::count())->toBe(0);

    Event::assertDispatchedOnce(DeletingSection::class);
    Event::assertDispatchedOnce(ApplyingSectionDelete::class);
    Event::assertDispatchedOnce(SectionDeleted::class);
});

it('deletes site sections belonging to a deleted site', function () {
    $siteSettings = SectionSiteSettings::factory()->create();
    $this->sections->refreshSections();
    Sites::refreshSites();
    SiteGroups::refreshGroups();
    ProjectConfig::rebuild();

    $projectConfigKey = ProjectConfigService::PATH_SECTIONS.'.'.$siteSettings->section->uid.'.siteSettings.'.$siteSettings->site->uid;

    expect(ProjectConfig::get($projectConfigKey))->not()->toBeNull();

    $this->sections->pruneDeletedSite(new SiteDeleted(
        Sites::getSiteById($siteSettings->siteId),
    ));

    expect(ProjectConfig::get($projectConfigKey))->toBeNull();
});

it('can get table data', function () {
    // Mostly a smoke test to check there are no exceptions
    expect($this->sections->getSectionTableData(1, 100))->not()->toBeEmpty();
});
