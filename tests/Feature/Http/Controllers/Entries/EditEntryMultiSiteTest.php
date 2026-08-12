<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\FieldLayoutTab;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout as FieldLayoutModel;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    actingAs(User::findOne());

    $layout = FieldLayout::make(Entry::class)
        ->tab('Content', fn (FieldLayoutTab $tab) => $tab->add(new EntryTitleField(['uid' => 'entry-title'])));
    $layoutModel = FieldLayoutModel::factory()->create([
        'type' => Entry::class,
        'config' => $layout->getConfig(),
    ]);
    $this->entryType = EntryType::factory()->create(['fieldLayoutId' => $layoutModel->id]);
    $this->section = Section::factory()->withEntryTypes($this->entryType)->create(['handle' => 'news']);

    $this->secondSite = Site::factory()->create();
    Sites::refreshSites();
    SectionSiteSettings::factory()->create([
        'sectionId' => $this->section->id,
        'siteId' => $this->secondSite->id,
        'hasUrls' => true,
        'dateCreated' => $this->section->dateCreated,
        'dateUpdated' => $this->section->dateUpdated,
    ]);
    Sections::refreshSections();

    $this->entry = EntryModel::factory()
        ->forSection($this->section)
        ->forEntryType($this->entryType)
        ->createElement([
            'title' => 'Current Title',
            'slug' => 'current-title',
        ]);
});

it('leads the breadcrumbs with a site switcher', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs', function (Collection $crumbs) {
                $siteCrumb = $crumbs->first();

                return ($siteCrumb['icon'] ?? null) === 'earth'
                    && collect($siteCrumb['actions'] ?? [])
                        ->pluck('label')
                        ->contains($this->secondSite->name);
            })
            ->etc()
        );
});

it('points each site switcher link at the same element on that site', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs', function (Collection $crumbs) {
                $actions = collect($crumbs->first()['actions'] ?? []);

                return $actions->every(fn (array $action) => str_contains(
                    (string) $action['href'],
                    (string) $this->entry->id,
                ))
                    && $actions->contains(fn (array $action) => str_contains(
                        (string) $action['href'],
                        "site={$this->secondSite->handle}",
                    ));
            })
            ->etc()
        );
});

it('renders a global status switch alongside per-site switches', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarForm.nodes', function (Collection $nodes) {
                $paths = $nodes
                    ->map(fn (array $node) => implode('.', $node['control']['path'] ?? []))
                    ->all();

                $group = $nodes->first(fn (array $node) => ($node['uid'] ?? null) === 'site-statuses');

                $sitePaths = collect($group['children'] ?? [])
                    ->map(fn (array $child) => implode('.', $child['control']['path'] ?? []))
                    ->all();

                return in_array('enabled', $paths, true)
                    && in_array("enabledForSite.{$this->entry->siteId}", $sitePaths, true)
                    && in_array("enabledForSite.{$this->secondSite->id}", $sitePaths, true);
            })
            ->etc()
        );
});

it('omits the site switcher and per-site switches when the section is single-site', function () {
    $section = Section::factory()->withEntryTypes($this->entryType)->create(['handle' => 'solo']);
    Sections::refreshSections();

    $entry = EntryModel::factory()
        ->forSection($section)
        ->forEntryType($this->entryType)
        ->createElement(['title' => 'Solo', 'slug' => 'solo']);

    get($entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('crumbs', fn (Collection $crumbs) => $crumbs->doesntContain(
                fn (array $crumb) => ($crumb['icon'] ?? null) === 'earth',
            ))
            ->where('sidebarForm.nodes', fn (Collection $nodes) => $nodes->doesntContain(
                fn (array $node) => ($node['uid'] ?? null) === 'site-statuses',
            ))
            ->etc()
        );
});

it('offers a switch for every site the section is enabled for', function () {
    $extraSite = Site::factory()->create();
    Sites::refreshSites();
    SectionSiteSettings::factory()->create([
        'sectionId' => $this->section->id,
        'siteId' => $extraSite->id,
        'hasUrls' => true,
        'dateCreated' => $this->section->dateCreated,
        'dateUpdated' => $this->section->dateUpdated,
    ]);
    Sections::refreshSections();

    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarForm.nodes', function (Collection $nodes) use ($extraSite) {
                $group = $nodes->first(fn (array $node) => ($node['uid'] ?? null) === 'site-statuses');

                return collect($group['children'] ?? [])
                    ->map(fn (array $child) => implode('.', $child['control']['path'] ?? []))
                    ->contains("enabledForSite.{$extraSite->id}");
            })
            ->etc()
        );
});
