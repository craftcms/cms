<?php

namespace craft\imagetransforms;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Image\ImageTransformHelper;
use CraftCms\Cms\Support\Url;
use Illuminate\Support\Facades\Crypt;

/**
 * FallbackTransformer transforms image assets using GD or ImageMagick, and stores them in the storage folder.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0
 */
class FallbackTransformer implements \craft\base\imagetransforms\ImageTransformerInterface
{
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        if (match ($asset->getMimeType()) {
            'image/gif' => Cms::config()->transformGifs,
            'image/svg+xml' => Cms::config()->transformSvgs,
            default => true,
        }) {
            $transformString = ltrim(ImageTransformHelper::getTransformString($imageTransform, true), '_');
        } else {
            $transformString = 'original';
        }

        return Url::actionUrl('assets/generate-fallback-transform', [
            'transform' => Crypt::encrypt(sprintf('%s,%s', $asset->id, $transformString)),
        ] + AssetsHelper::revParams($asset));
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        // No reliable way to do this, so not worth trying
    }
}
