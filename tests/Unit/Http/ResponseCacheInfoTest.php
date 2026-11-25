<?php

declare(strict_types=1);

use CraftCms\Cms\Http\ResponseCacheInfo;

it('ignores empty and duplicate tags', function () {
    $cacheInfo = app(ResponseCacheInfo::class)->addTags(['a', '', 'b'])->addTags('a');

    expect($cacheInfo->getTags())->toBe(['a', 'b']);
});

it('keeps the earliest expiry date', function () {
    $cacheInfo = app(ResponseCacheInfo::class);

    expect($cacheInfo->getExpiryDate())->toBeNull();

    $cacheInfo
        ->addExpiryDate(new DateTimeImmutable('@2000'))
        ->addExpiryDate(new DateTimeImmutable('@1000'))
        ->addExpiryDate(new DateTimeImmutable('@3000'));

    expect($cacheInfo->getExpiryDate()?->getTimestamp())->toBe(1000);
});
