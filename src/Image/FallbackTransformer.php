<?php

declare(strict_types=1);

namespace CraftCms\Cms\Image;

use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Image\Contracts\ImageTransformerInterface;
use CraftCms\Cms\Image\Data\ImageTransform;
use CraftCms\Cms\Support\URL;
use Illuminate\Support\Facades\Crypt;

readonly class FallbackTransformer implements ImageTransformerInterface
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

        return URL::actionUrl('assets/generate-fallback-transform', [
            'transform' => Crypt::encrypt(sprintf('%s,%s', $asset->id, $transformString)),
        ] + AssetsHelper::revParams($asset), showScriptName: false);
    }

    public function invalidateAssetTransforms(Asset $asset): void
    {
        // No reliable way to do this, so not worth trying
    }
}
