<?php

namespace craft\base\imagetransforms;

use craft\elements\Asset;
use craft\models\ImageTransform;

interface ImageTransformerInterface
{
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string;

    public function invalidateAssetTransforms(Asset $asset): void;
}
