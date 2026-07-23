<?php

declare(strict_types=1);

use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Jobs\ApplyNewPropagationMethod;
use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\ProjectConfig\Events\ConfigEvent;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Section\Data\Section as SectionData;
use CraftCms\Cms\Section\Data\SectionSiteSettings as SectionSiteSettingsData;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Section\Events\SectionDeleted;
use CraftCms\Cms\Section\Events\SectionDeleting;
use CraftCms\Cms\Section\Events\SectionDeletionApplying;
use CraftCms\Cms\Section\Events\SectionSaved;
use CraftCms\Cms\Section\Events\SectionSaving;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Section\Sections;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sections as SectionsFacade;
use CraftCms\Cms\Support\Facades\SiteGroups;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

use function Pest\Laravel\actingAs;

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

it('can get available entry move target sections', function () {
    Edition::set(Edition::Pro);

    $entryType = EntryType::factory()->create();
    $otherEntryType = EntryType::factory()->create();
    $currentSite = Sites::getCurrentSite();
    $otherSite = Site::factory()->create();

    $currentSection = Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'Current',
        'handle' => 'current',
    ]);

    $targetBSection = Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'B Target',
        'handle' => 'target-b',
    ]);

    $targetASection = Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'A Target',
        'handle' => 'target-a',
    ]);

    Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'Single',
        'handle' => 'single',
        'type' => SectionType::Single,
    ]);

    $wrongSiteSection = Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'Wrong Site',
        'handle' => 'wrong-site',
    ]);
    SectionSiteSettings::query()
        ->where('sectionId', $wrongSiteSection->id)
        ->delete();
    SectionSiteSettings::factory()->create([
        'sectionId' => $wrongSiteSection->id,
        'siteId' => $otherSite->id,
    ]);

    Section::factory()->withEntryTypes($otherEntryType)->create([
        'name' => 'Wrong Type',
        'handle' => 'wrong-type',
    ]);

    Section::factory()->withEntryTypes($entryType)->create([
        'name' => 'Unauthorized',
        'handle' => 'unauthorized',
    ]);

    $user = UserModel::factory()
        ->withPermissions([
            "viewEntries:{$targetASection->uid}",
            "saveEntries:{$targetASection->uid}",
            "viewEntries:{$targetBSection->uid}",
            "saveEntries:{$targetBSection->uid}",
        ])
        ->create();

    actingAs($user);
    $this->sections->refreshSections();

    $availableSections = $this->sections->getAvailableEntryMoveTargetSections(
        entryTypeIds: [$entryType->id],
        siteId: $currentSite->id,
        currentSectionUid: $currentSection->uid,
    );

    expect(array_values(array_map(fn (SectionData $section) => $section->uid, $availableSections)))->toBe([
        $targetASection->uid,
        $targetBSection->uid,
    ]);
});

it('only returns entry move target sections that support every entry type', function () {
    Edition::set(Edition::Pro);

    $entryTypeA = EntryType::factory()->create();
    $entryTypeB = EntryType::factory()->create();
    $currentSection = Section::factory()->withEntryTypes($entryTypeA, $entryTypeB)->create();
    $compatibleSection = Section::factory()->withEntryTypes($entryTypeA, $entryTypeB)->create();
    $partiallyCompatibleSection = Section::factory()->withEntryTypes($entryTypeA)->create();

    $user = UserModel::factory()
        ->withPermissions([
            "viewEntries:{$compatibleSection->uid}",
            "saveEntries:{$compatibleSection->uid}",
            "viewEntries:{$partiallyCompatibleSection->uid}",
            "saveEntries:{$partiallyCompatibleSection->uid}",
        ])
        ->create();

    actingAs($user);
    $this->sections->refreshSections();

    $availableSections = $this->sections->getAvailableEntryMoveTargetSections(
        entryTypeIds: [$entryTypeA->id, $entryTypeB->id],
        siteId: Sites::getCurrentSite()->id,
        currentSectionUid: $currentSection->uid,
    );

    expect(array_values(array_map(fn (SectionData $section) => $section->uid, $availableSections)))
        ->toBe([$compatibleSection->uid]);
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
        SectionSaving::class,
        SectionSaved::class,
    ]);

    Event::listen(SectionSaving::class, fn () => null);
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

    Event::assertDispatchedOnce(SectionSaving::class);
    Event::assertDispatchedOnce(SectionSaved::class);
});

it('queues section jobs after committing section changes', function (string $configKey, string $value, string $jobClass) {
    $sectionModel = Section::factory()->create([
        'propagationMethod' => PropagationMethod::All,
    ]);
    $this->sections->refreshSections();

    $section = $this->sections->getSectionById($sectionModel->id);
    $config = $section->getConfig();
    $config[$configKey] = $value;

    Queue::fake();

    $this->sections->handleChangedSection(new class(path: ProjectConfigService::PATH_SECTIONS.'.'.$section->uid, newValue: $config, tokenMatches: [$section->uid]) extends ConfigEvent {});

    Queue::assertPushed($jobClass, fn (object $job) => $job->afterCommit === true);
})->with([
    'propagation updates' => ['propagationMethod', PropagationMethod::SiteGroup->value, ApplyNewPropagationMethod::class],
    'section resaves' => ['handle', 'updatedSection', ResaveElements::class],
]);

it('can delete a section by id', function () {
    Event::fake([
        SectionDeleting::class,
        SectionDeletionApplying::class,
        SectionDeleted::class,
    ]);

    Event::listen(SectionDeleting::class, fn () => null);
    Event::listen(SectionDeletionApplying::class, fn () => null);
    Event::listen(SectionDeleted::class, fn () => null);

    $section = Section::factory()->create();
    $this->sections->refreshSections();
    ProjectConfig::rebuild();

    expect(Section::count())->toBe(1);

    expect($this->sections->deleteSectionById($section->id))->toBeTrue();

    expect(Section::count())->toBe(0);

    Event::assertDispatchedOnce(SectionDeleting::class);
    Event::assertDispatchedOnce(SectionDeletionApplying::class);
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

it('returns laravel-style pagination metadata for table data', function () {
    Section::factory()->count(3)->create();
    $this->sections->refreshSections();

    [$pagination] = $this->sections->getSectionTableData(2, 2);

    expect($pagination)
        ->toMatchArray([
            'total' => 3,
            'per_page' => 2,
            'current_page' => 2,
            'last_page' => 2,
            'from' => 3,
            'to' => 3,
        ])
        ->and($pagination['prev_page_url'])->toContain('page=1')
        ->and($pagination['next_page_url'])->toBeNull();
});
