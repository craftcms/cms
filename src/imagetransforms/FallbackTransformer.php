<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\imagetransforms;

use Craft;
use craft\base\Component;
use craft\base\imagetransforms\ImageTransformerInterface;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\helpers\ImageTransforms;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;

/**
 * FallbackTransformer transforms image assets using GD or ImageMagick, and stores them in the storage folder.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class FallbackTransformer extends Component implements ImageTransformerInterface
{
    /**
     * @inheritdoc
     */
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform, bool $immediately): string
    {
        if (match ($asset->getMimeType()) {
            'image/gif' => Craft::$app->getConfig()->getGeneral()->transformGifs,
            'image/svg+xml' => Craft::$app->getConfig()->getGeneral()->transformSvgs,
            default => true,
        }) {
            $transformString = ltrim(ImageTransforms::getTransformString($imageTransform, true), '_');
        } else {
            $transformString = 'original';
        }

        $security = Craft::$app->getSecurity();
        return UrlHelper::actionUrl('assets/generate-fallback-transform', [
            'transform' => $security->hashData(sprintf('%s,%s', $asset->id, $transformString)),
        ] + Assets::revParams($asset), showScriptName: false);
    }

    /**
     * @inheritdoc
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        // No reliable way to do this, so not worth trying
    }
}
