<?php

declare(strict_types=1);

use CraftCms\Cms\Http\ResponseCacheInfo;

it('ignores empty and duplicate tags', function () {
    $cacheInfo = (new ResponseCacheInfo)->addTags(['a', '', 'b'])->addTags('a');

    expect($cacheInfo->getTags())->toBe(['a', 'b']);
});

it('captures the tags added inside nested blocks', function () {
    $cacheInfo = new ResponseCacheInfo;

    $cacheInfo->addTags(['page']);
    $cacheInfo->startCapture();
    $cacheInfo->addTags(['outer', 'page']);
    $cacheInfo->startCapture();
    $cacheInfo->addTags(['inner']);

    expect($cacheInfo->stopCapture())->toBe(['inner'])
        ->and($cacheInfo->stopCapture())->toBe(['outer', 'page', 'inner']);
});

it('keeps the earliest expiry date', function () {
    $cacheInfo = new ResponseCacheInfo;

    expect($cacheInfo->getExpiryDate())->toBeNull();

    $cacheInfo
        ->addExpiryDate(new DateTimeImmutable('@2000'))
        ->addExpiryDate(new DateTimeImmutable('@1000'))
        ->addExpiryDate(new DateTimeImmutable('@3000'));

    expect($cacheInfo->getExpiryDate()?->getTimestamp())->toBe(1000);
});
