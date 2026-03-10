<?php

declare(strict_types=1);

use CraftCms\Cms\View\Events\RegisterTemplateCacheCollectors;
use CraftCms\Yii2Adapter\View\LegacyAssetBundleCollector;
use Illuminate\Support\Collection;

it('registers the legacy asset bundle collector', function() {
    $event = new RegisterTemplateCacheCollectors(Collection::make());

    event($event);

    expect($event->types)->toContain(LegacyAssetBundleCollector::class);
});
