<?php

declare(strict_types=1);
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\image\transforms;

use craft\elements\Asset;
use craft\helpers\UrlHelper;
use craft\models\ImageTransform;

/**
 * Imgix stub
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 */
class Imgix implements TransformerInterface
{
    /**
     * Returns the URL for an image asset transform.
     *
     * @return string The URL for the transform
     */
    public function getTransformUrl(Asset $asset, ImageTransform $imageTransform): string
    {
        $base = 'https://umbushka.imgix.net/' . $asset->getPath();
        $params = [];

        switch ($imageTransform->mode) {
            case 'crop':
                $params['fit'] = 'crop';
                break;
            case 'stretch':
                $params['fit'] = 'fill';
                break;
            default:
                $params['fit'] = 'fillmax';
        }

        $params['h'] = $imageTransform->height;
        $params['w'] = $imageTransform->width;
        $params['q'] = $imageTransform->quality;

        $position = array_filter(explode('-', $imageTransform->position), fn($value) => $value !== 'center');

        if (!empty($position)) {
            $params['crop'] = implode(',', $position);
        }

        return UrlHelper::url($base, $params);
    }

    /**
     * @inheritdoc
     */
    public function invalidateAssetTransforms(Asset $asset): void
    {
    }
}
