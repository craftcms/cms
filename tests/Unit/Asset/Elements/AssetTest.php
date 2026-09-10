<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\User\Elements\User;

/**
 * `uploader` is a magic property that resolves to `getUploader()`. Since eager-loading the
 * `uploader` handle bypasses the generic `Element::$_eagerLoadedElements` array (see
 * `Asset::setEagerLoadedElements()`), property access should always match `getUploader()`,
 * whether or not the uploader has been eager-loaded.
 */
test('uploader property matches getUploader()', function () {
    // No uploader set at all
    $asset = new Asset(['id' => 1]);
    expect($asset->getUploader())->toBeNull()->and($asset->uploader)->toBe($asset->getUploader());

    // Uploader set directly, not via eager-loading
    $uploader = new User(['id' => 100]);
    $asset->setUploader($uploader);
    expect($asset->getUploader())->toBe($uploader)->and($asset->uploader)->toBe($asset->getUploader());

    // Uploader eager-loaded
    $asset2 = new Asset(['id' => 2]);
    $plan = new EagerLoadPlan(handle: 'uploader');
    $asset2->setEagerLoadedElements('uploader', [$uploader], $plan);
    expect($asset2->getUploader())->toBe($uploader)->and($asset2->uploader)->toBe($asset2->getUploader());

    // No uploader eager-loaded (empty result)
    $asset3 = new Asset(['id' => 3]);
    $asset3->setEagerLoadedElements('uploader', [], $plan);
    expect($asset3->getUploader())->toBeNull()->and($asset3->uploader)->toBe($asset3->getUploader());
});
