<?php

declare(strict_types=1);

use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Http\ResponseCacheInfo;

it('collects nothing outside a collection', function () {
    $cacheInfo = app(ResponseCacheInfo::class)->addTags(['a'])->addExpiryDate(now()->addHour());

    expect($cacheInfo->getTags())->toBe([])
        ->and($cacheInfo->getExpiryDate())->toBeNull();
});

it('ignores empty and duplicate tags and keeps the earliest future expiry date until it stops', function () {
    $this->freezeSecond();
    $cacheInfo = app(ResponseCacheInfo::class);
    $cacheInfo->start();

    $cacheInfo
        ->addTags(['a', '', 'b'])->addTags('a')
        ->addExpiryDate(now()->addMinutes(20))
        ->addExpiryDate(now()->addMinutes(10))
        ->addExpiryDate(now()->subMinute())
        ->addExpiryDate(now()->addMinutes(30));

    expect($cacheInfo->getTags())->toBe(['a', 'b'])
        ->and($cacheInfo->getExpiryDate()?->getTimestamp())->toBe(now()->addMinutes(10)->getTimestamp());

    $cacheInfo->stop();

    expect($cacheInfo->getTags())->toBe([])
        ->and($cacheInfo->getExpiryDate())->toBeNull();
});

it('includes tags from collections still open inside the response', function () {
    $cacheInfo = app(ResponseCacheInfo::class);
    $cacheInfo->start();
    $cacheInfo->addTags('page');

    app(ElementCaches::class)->startCollectingCacheInfo();
    $cacheInfo->addTags('inner');

    expect($cacheInfo->getTags())->toEqualCanonicalizing(['page', 'inner']);

    app(ElementCaches::class)->stopCollectingCacheInfo();

    expect($cacheInfo->getTags())->toEqualCanonicalizing(['page', 'inner']);
});
