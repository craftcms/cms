<?php

declare(strict_types=1);

use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Data\SiteGroup;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Site\Sites;

beforeEach(function () {
    $this->sites = app(Sites::class);
    $this->siteGroups = app(SiteGroups::class);
});

describe('index', function () {
    beforeEach(function () {
        $this->page = visitCpAsAdmin('settings/sites');
    });

    it('renders the index page', function () {
        $this->page->assertSee('Sites')
            ->assertTitleContains('Sites');
    });

    it('renders a group index page')->todo();

    it('can rename a group')->todo();
    it('can delete a group')->todo();
    it('can create a group')->todo();

    it('allows reordering sites')->todo();
});

describe('create', function () {
    beforeEach(function () {
        $this->page = visitCpAsAdmin('settings/sites/new');
    });

    it('creates a site', function () {
        $this->page->assertSee('Create a new site');
    });

    // Skipping for now because a separate PR has the composable to do this
    it('automatically fills handle', function () {
        $this->page->fill('#name input', 'Test site');
        expect($this->page->value('#handle input'))->toBe('test-site');
    })->skip();
});

describe('edit', function () {
    it('renders the edit page', function () {
        $this->siteGroups->saveGroup($group = new SiteGroup(['name' => 'New group']));
        $this->sites->saveSite(new SiteData([
            'name' => 'New site',
            'handle' => 'newSite',
            'language' => 'nl',
            'groupId' => $group->id,
        ]));
        $site = $this->sites->getSiteByHandle('newSite');

        visitCpAsAdmin('settings/sites/'.$site->id)
            ->assertSee($site->name)
            ->assertTitleContains($site->name);
    });

    it('shows and hides the base URL input', function () {
        $this->siteGroups->saveGroup($group = new SiteGroup(['name' => 'New group']));
        $this->sites->saveSite(new SiteData([
            'name' => 'New site',
            'handle' => 'newSite',
            'language' => 'nl',
            'hasUrls' => true,
            'groupId' => $group->id,
        ]));
        $site = $this->sites->getSiteByHandle('newSite');

        $page = visitCpAsAdmin('settings/sites/'.$site->id);

        $page->assertVisible('#base-url input');
        $page->click('#has-urls');

        $page->assertMissing('#baseUrl input');
    });

    it('successfully edits a site', function () {
        $this->siteGroups->saveGroup($group = new SiteGroup(['name' => 'New group']));
        $this->siteGroups->saveGroup(new SiteGroup(['name' => 'Second']));
        $this->sites->saveSite(new SiteData([
            'name' => 'New site',
            'handle' => 'newSite',
            'enabled' => true,
            'language' => 'nl',
            'groupId' => $group->id,
        ]));
        $site = $this->sites->getSiteByHandle('newSite');

        $page = visitCpAsAdmin('settings/sites/'.$site->id);

        $page->select('#group select', 'Second');

        $page->fill('#name input', 'Totally different name')
            ->keys('#name input', 'Enter');

        $page->fill('#handle input', 'totallyDifferentHandle');
        $page->fill('#site-language input', 'en-us')
            ->keys('#site-language input', 'Enter');

        $page->fill('#enabled input', 'Disabled')
            ->keys('#enabled input', 'ArrowDown')
            ->keys('#enabled input', 'Enter');

        $page->click('Save');
        $page->assertSee('Site saved.');

        \Pest\Laravel\assertDatabaseHas('sites', [
            'id' => $site->id,
            'name' => 'Totally different name',
            'handle' => 'totallyDifferentHandle',
            'language' => 'en-US',
            'hasUrls' => 1,
        ]);
    });

    it('saves with keyboard shortcuts')->todo();
});
