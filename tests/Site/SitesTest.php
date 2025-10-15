<?php

use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Facades\Sites as SitesFacade;
use CraftCms\Cms\User\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->sites = app(Sites::class);
});

it('is a singleton', function () {
    expect($this->sites)->toBe(app(Sites::class));
    expect($this->sites)->toBe(SitesFacade::getFacadeRoot());
});

it('can get sites by group id', function () {
    expect($this->sites->getSitesByGroupId(999))->toBeEmpty();

    Site::factory()->create([
        'groupId' => 1,
    ]);

    expect($this->sites->getSitesByGroupId(1))->toHaveCount(1);
});

it('can get total amount of sites', function () {
    expect($this->sites->getTotalSites())->toBe(1);

    Site::factory()->create();

    expect($this->sites->getTotalSites())->toBe(1);

    SitesFacade::refreshSites();

    expect($this->sites->getTotalSites())->toBe(2);
});

it('can get total editable sites', function () {
    // Not in multisite always returns the default site
    expect($this->sites->getTotalEditableSites())->toBe(1);

    Site::factory()->create();
    SitesFacade::refreshSites();

    // Not logged in is 0 sites.
    expect($this->sites->getTotalEditableSites())->toBe(0);

    actingAs(User::first());
    SitesFacade::refreshSites();

    expect($this->sites->getTotalEditableSites())->toBe(2);
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
