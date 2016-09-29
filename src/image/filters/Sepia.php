<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\image\filters;

use Craft;
use craft\app\base\ImageFilter;

/**
 * Class Sepia
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Sepia extends ImageFilter
{


    /**
     * Return a string representation of the filter.
     *
     * @return string
     */
    public function __toString() {
        return Craft::t('app', 'Sepia');
    }

    /**
     * @inheritdoc
     */
    public function canApplyFilter()
    {
        return Craft::$app->getImages()->getIsImagick();
    }

    /**
     * @inheritdoc
     */
    public function applyAndReturnBlob($imagePath, $options = [])
    {
        $image = new \Imagick($imagePath);
        $this->applyFilter($image, $options);
        return $image->getImageBlob();
    }

    /**
     * @inheritdoc
     */
    public function applyAndStore($imagePath, $options = [], $targetPath = '')
    {
        $targetPath = empty($targetPath) ? $imagePath : $targetPath;
        $image = new \Imagick($imagePath);
        $this->applyFilter($image, $options);
        return $image->writeImage($targetPath);
    }

    /**
     * @inheritdoc
     */
    protected function applyFilter(\Imagick $image, $options = [])
    {
        $image->sepiaToneImage(80);
        return $image;
    }
}
