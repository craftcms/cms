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
 * Class Brightness
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Brightness extends ImageFilter
{


    /**
     * Return a string representation of the filter.
     *
     * @return string
     */
    public function __toString() {
        return Craft::t('app', 'Brightness');
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
    public function getFieldHtml()
    {
        $brightness = '<label for="gamma">'.Craft::t('app', 'Gamma').'</label> <input id="gamma" name="gamma" type="range" min="0.5" max="3" step="0.01" value="1"/>';
        return $brightness;
    }

    /**
     * @inheritdoc
     */
    protected function applyFilter(\Imagick $image, $options = [])
    {
        // Defaults
        $options = array_merge(['gamma' => 1], $options);
        $image->gammaImage($options['gamma']);
        return $image;
    }
}
