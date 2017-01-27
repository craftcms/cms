<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\image\filters;

use Craft;
use craft\base\ImageFilter;

/**
 * Class Grayscale
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Grayscale extends ImageFilter
{
    /**
     * Return a string representation of the filter.
     *
     * @return string
     */
    public function __toString()
    {
        return Craft::t('app', 'Grayscale');
    }

    /**
     * @inheritdoc
     */
    public function canApplyFilter(): bool
    {
        return Craft::$app->getImages()->getIsImagick();
    }

    /**
     * @inheritdoc
     */
    public function applyAndReturnBlob(string $imagePath, array $options = [])
    {
        $image = new \Imagick($imagePath);
        $this->applyFilter($image, $options);

        return $image->getImageBlob();
    }

    /**
     * @inheritdoc
     */
    public function applyAndStore(string $imagePath, array $options = [], string $targetPath = ''): bool
    {
        $targetPath = empty($targetPath) ? $imagePath : $targetPath;

        $image = new \Imagick($imagePath);
        $this->applyFilter($image, $options);

        return $image->writeImage($targetPath);
    }

    /**
     * @inheritdoc
     */
    protected function applyFilter(\Imagick $image, array $options = []): \Imagick
    {
        $image->transformImageColorspace(\Imagick::COLORSPACE_GRAY);

        return $image;
    }
}
