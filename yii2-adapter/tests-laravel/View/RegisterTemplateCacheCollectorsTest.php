<?php

declare(strict_types=1);

use CraftCms\Cms\View\TemplateCacheCollectors;
use CraftCms\Yii2Adapter\View\LegacyAssetBundleCollector;

it('registers the legacy asset bundle collector', function() {
    expect(app(TemplateCacheCollectors::class)->types())->toContain(LegacyAssetBundleCollector::class);
});
