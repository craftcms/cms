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
        $transformString = ltrim(ImageTransforms::getTransformString($imageTransform, true), '_');
        return UrlHelper::actionUrl('assets/generate-fallback-transform', [
            'assetId' => $asset->id,
            'transform' => Craft::$app->getSecurity()->hashData($transformString),
        ], showScriptName: false);
    }

    /**
     * @inheritdoc
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
        // No reliable way to do this, so not worth trying
    }
}
