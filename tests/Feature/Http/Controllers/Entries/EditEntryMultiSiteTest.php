<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\Icons;
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

                // Already canonical: the client fetches `<name>.svg` with none
                // of PHP's alias map, so `earth` — the Craft 5 alias — 404s in
                // the browser.
                $icon = $siteCrumb['icon'] ?? null;

                return is_string($icon)
                    && Icons::resolveIconName($icon) === $icon
                    && collect($siteCrumb['items'] ?? [])
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
                $actions = collect($crumbs->first()['items'] ?? []);

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

it('renders a Sites pane with a status switch for each site', function () {
    get($this->entry->getCpEditUrl())
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('sidebarUi', function (Collection $sidebarUi) {
                $control = collect($sidebarUi['nodes'])
                    ->first(fn (array $node) => ($node['control']['component'] ?? null) === 'craft:element-sites')['control'] ?? null;

                return $control !== null
                    && $control['path'] === ['enabledForSite']
                    && $control['props']['withStatuses'] === true
                    && collect($control['props']['sites'])->pluck('id')->all() === [$this->entry->siteId, $this->secondSite->id]
                    && $control['props']['sites'][0]['current'] === true
                    && str_contains((string) $control['props']['sites'][1]['url'], "site={$this->secondSite->handle}")
                    && array_keys($sidebarUi['values']['enabledForSite']) === [$this->entry->siteId, $this->secondSite->id];
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
            ->where('sidebarUi.nodes', fn (Collection $nodes) => $nodes->doesntContain(
                fn (array $node) => ($node['uid'] ?? null) === 'site-statuses'
                    || ($node['control']['component'] ?? null) === 'craft:element-sites',
            ))
            ->etc()
        );
});

it('lists every site the section is enabled for in the Sites pane', function () {
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
            ->where('sidebarUi.nodes', function (Collection $nodes) use ($extraSite) {
                $control = $nodes->first(fn (array $node) => ($node['control']['component'] ?? null) === 'craft:element-sites')['control'] ?? null;

                return collect($control['props']['sites'] ?? [])->pluck('id')->contains($extraSite->id);
            })
            ->etc()
        );
});
