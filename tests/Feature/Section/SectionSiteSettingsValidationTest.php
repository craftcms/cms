<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Section\Data\SectionSiteSettings;

it('names the entry URI format in its errors', function () {
    $settings = new SectionSiteSettings([
        'hasUrls' => true,
        'uriFormat' => Cms::config()->cpTrigger.'/{slug}',
    ]);

    $settings->validate();

    expect($settings->errors()->first('uriFormat'))
        ->toBe('Entry URI Format cannot start with the cpTrigger config setting.');
});
