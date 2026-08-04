<?php

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Site\SiteGroups;
use CraftCms\Cms\Support\Facades\SiteGroups as SiteGroupsFacade;

beforeEach(function () {
    $this->siteGroups = app(SiteGroups::class);
    $this->projectConfig = app(ProjectConfig::class);
});

it('is a singleton', function () {
    expect($this->siteGroups)->toBe(app(SiteGroups::class));
    expect($this->siteGroups)->toBe(SiteGroupsFacade::getFacadeRoot());
});
