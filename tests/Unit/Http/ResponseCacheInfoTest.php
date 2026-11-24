<?php

declare(strict_types=1);

use CraftCms\Cms\HttpCache\ResponseCacheInfo;

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
