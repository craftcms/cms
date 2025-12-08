<?php

declare(strict_types=1);

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Events\ApplyingSiteDelete;
use CraftCms\Cms\Site\Events\DeletingSite;
use CraftCms\Cms\Site\Events\ReorderingSites;
use CraftCms\Cms\Site\Events\SavingSite;
use CraftCms\Cms\Site\Events\SiteDeleted;
use CraftCms\Cms\Site\Events\SiteSaved;
use CraftCms\Cms\Site\Events\SitesReordered;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Models\SiteGroup;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Sites as SitesFacade;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->sites = app(Sites::class);
    $this->projectConfig = app(ProjectConfig::class);
});

it('is a singleton', function () {
    expect($this->sites)->toBe(app(Sites::class));
    expect($this->sites)->toBe(SitesFacade::getFacadeRoot());
});

it('can determine if its multisite', function () {
    expect($this->sites->isMultisite())->toBeFalse();

    $site = Site::factory()->create();
    $this->sites->refreshSites();

    expect($this->sites->isMultisite())->toBeTrue();

    $site->delete();
    $this->sites->refreshSites();

    expect($this->sites->isMultisite())->toBeFalse();
    expect($this->sites->isMultisite(refresh: true, withTrashed: true))->toBeTrue();
    expect($this->sites->isMultiSiteWithTrashed())->toBeTrue();
});

it('can get all site ids', function () {
    expect($this->sites->getAllSiteIds())->toHaveCount(1);
});

it('can get site by uid', function () {
    expect($this->sites->getSiteByUid(Site::first()->uid)->handle)->toBe(Site::first()->handle);
});

it('can determine if the current site has been set', function () {
    expect($this->sites->getHasCurrentSite())->toBeTrue();

    app()->forgetInstance(Sites::class);

    expect(app(Sites::class)->getHasCurrentSite())->toBeFalse();
});

it('can get and set the current site', function () {
    $otherSite = Site::factory()->create();
    $this->sites->refreshSites();

    expect($this->sites->getCurrentSite()->id)->toBe(Site::first()->id);

    $this->sites->setCurrentSite($this->sites->getSiteById($otherSite->id));

    expect($this->sites->getCurrentSite()->id)->toBe($otherSite->id);
});

it('can set the current site by handle', function () {
    $otherSite = Site::factory()->create();
    $this->sites->refreshSites();

    $this->sites->setCurrentSite($otherSite->handle);

    expect($this->sites->getCurrentSite()->id)->toBe($otherSite->id);
});

it('can set the current site by id', function () {
    $otherSite = Site::factory()->create();
    $this->sites->refreshSites();

    $this->sites->setCurrentSite($otherSite->id);

    expect($this->sites->getCurrentSite()->id)->toBe($otherSite->id);
});

it('can get the primary site', function () {
    expect($this->sites->getPrimarySite()->id)->toBe(Site::first()->id);
});

it('can get editable sites', function () {
    // Not in multisite always returns the default site
    expect($this->sites->getTotalEditableSites())->toBe(1);
    expect($this->sites->getEditableSiteIds())->toHaveCount(1);
    expect($this->sites->getEditableSites())->toHaveCount(1);

    Site::factory()->create();
    $this->sites->refreshSites();

    // Not logged in is 0 sites.
    expect($this->sites->getTotalEditableSites())->toBe(0);
    expect($this->sites->getEditableSiteIds())->toHaveCount(0);
    expect($this->sites->getEditableSites())->toHaveCount(0);

    actingAs(User::find()->firstOrFail());
    $this->sites->refreshSites();

    expect($this->sites->getTotalEditableSites())->toBe(2);
    expect($this->sites->getEditableSiteIds())->toHaveCount(2);
    expect($this->sites->getEditableSites())->toHaveCount(2);
});

it('can get all sites', function () {
    expect($this->sites->getAllSites())->toHaveCount(1);

    Site::factory()->create();
    $this->sites->refreshSites();

    expect($this->sites->getAllSites())->toHaveCount(2);
});

it('can get sites by group id', function () {
    expect($this->sites->getSitesByGroupId(999))->toBeEmpty();
    expect($this->sites->getEditableSitesByGroupId(999))->toBeEmpty();

    Site::factory()->create([
        'groupId' => 1,
    ]);

    expect($this->sites->getSitesByGroupId(1))->toHaveCount(1);
    expect($this->sites->getEditableSitesByGroupId(1))->toHaveCount(1);
});

it('can get total amount of sites', function () {
    expect($this->sites->getTotalSites())->toBe(1);

    Site::factory()->create();

    expect($this->sites->getTotalSites())->toBe(1);

    SitesFacade::refreshSites();

    expect($this->sites->getTotalSites())->toBe(2);
});

it('can get sites by id', function () {
    expect($this->sites->getSiteById(1))->not()->toBeNull();
    expect($this->sites->getSiteById(999))->toBeNull();
});

it('can get sites by handle', function () {
    $defaultSite = Site::firstOrFail();

    expect($this->sites->getSiteByHandle($defaultSite->handle))->not()->toBeNull();
    expect($this->sites->getSiteByHandle('does-not-exist'))->toBeNull();
});

it('can get sites by language', function () {
    expect($this->sites->getSitesByLanguage('en-US'))->not()->toBeEmpty();
    expect($this->sites->getSitesByLanguage('xxx'))->toBeEmpty();
});

it('can get remaining sites', function () {
    expect($this->sites->getRemainingSites())->toBe(99);
});

it('can save a site', function () {
    /**
     * Retreive the model first so the install migration
     * runs before we start faking events
     */
    $siteModel = Site::firstOrFail();

    Event::fake([
        SavingSite::class,
        SiteSaved::class,
    ]);

    $site = $this->sites->getSiteByHandle($siteModel->handle);

    $site->setName('Edited name');

    $this->sites->saveSite($site);

    expect($this->sites->getSiteByHandle($siteModel->handle)->getName())->toBe('Edited name');
    Event::assertDispatchedOnce(SavingSite::class);
    Event::assertDispatchedOnce(SiteSaved::class);
});

it('can save a new site', function () {
    expect(Site::count())->toBe(1);

    $this->sites->saveSite($site = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    expect(Site::count())->toBe(2);
    tap(Site::query()->where('handle', 'new-site')->firstOrFail(), function (Site $site) {
        expect($site->name)->toBe('New site');
        expect($site->handle)->toBe('new-site');
        expect($site->language)->toBe('nl');
        expect($site->groupId)->toBe(SiteGroup::first()->id);
    });

    $projectConfigData = $this->projectConfig->get(ProjectConfig::PATH_SITES.'.'.$site->uid);
    expect($projectConfigData['name'])->toBe('New site');
    expect($projectConfigData['handle'])->toBe('new-site');
});

it('can reorder sites', function () {
    $this->sites->saveSite($otherSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    $defaultSite = Site::first();

    Event::fake([
        ReorderingSites::class,
        SitesReordered::class,
    ]);

    $this->projectConfig->rebuild();

    $this->sites->reorderSites([$otherSite->id, $defaultSite->id]);

    Event::assertDispatchedOnce(ReorderingSites::class);
    Event::assertDispatchedOnce(SitesReordered::class);

    expect($defaultSite->fresh()->sortOrder)->toBe(2);
    expect(Site::findOrFail($otherSite->id)->sortOrder)->toBe(1);
});

it('can delete a site by id', function () {
    $this->sites->saveSite($newSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    expect(Site::count())->toBe(2);

    $this->sites->deleteSiteById($newSite->id);

    expect(Site::count())->toBe(1);
    expect(Site::withTrashed()->count())->toBe(2);
});

it('can delete a site', function () {
    Event::fake([
        DeletingSite::class,
        ApplyingSiteDelete::class,
        SiteDeleted::class,
    ]);

    $this->sites->saveSite($newSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    expect(Site::count())->toBe(2);

    $this->sites->deleteSite($newSite);

    expect(Site::count())->toBe(1);
    expect(Site::withTrashed()->count())->toBe(2);

    Event::assertDispatchedOnce(DeletingSite::class);
    Event::assertDispatchedOnce(ApplyingSiteDelete::class);
    Event::assertDispatchedOnce(SiteDeleted::class);
});

it('can prevent deletion through an event', function () {
    $this->sites->saveSite($newSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    Event::listen(DeletingSite::class, function (DeletingSite $event) {
        $event->isValid = false;

        return false;
    });

    expect(Site::count())->toBe(2);

    expect($this->sites->deleteSite($newSite))->toBeFalse();

    expect(Site::count())->toBe(2);
});

it('can restore a site by id', function () {
    $this->sites->saveSite($newSite = new SiteData(
        name: 'New site',
        handle: 'new-site',
        language: 'nl',
        groupId: SiteGroup::first()->id,
    ));

    $this->sites->deleteSiteById($newSite->id);

    expect(Site::count())->toBe(1);

    $this->sites->restoreSiteById($newSite->id);

    expect(Site::count())->toBe(2);
});
