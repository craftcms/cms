<?php
namespace craft\app\services;

use Craft;

use craft\app\base\Component;
use craft\app\base\ImageFilterInterface;
use craft\app\helpers\Component as ComponentHelper;
use craft\app\image\filters\Grayscale;
use craft\app\image\filters\Sepia;

/**
 * Class ImageEffects
 *
 * @author     Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @copyright  Copyright (c) 2014, Pixel & Tonic, Inc.
 * @license    http://craftcms.com/license Craft License Agreement
 * @see        http://craftcms.com
 * @package    craft.app.services
 * @since      3.0
 */
class ImageEffects extends Component
{

    // Constants
    // =========================================================================

    /**
     * @var string The volume interface name
     */
    const IMAGE_FILTER_INTERFACE = 'craft\app\base\ImageFilterInterface';

    // Public Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string
     */
    public function __toString() {
        return static::displayName();
    }

    /**
     * Returns all available image filter types.
     *
     * @return array the available volume type classes
     */
    public function getAllFilterTypes()
    {
        $imageFilters = [
            Sepia::className(),
            Grayscale::className()
        ];

        foreach (Craft::$app->getPlugins()->call('getImageFilters', [], true) as $pluginFilters) {
            $imageFilters = array_merge($imageFilters, $pluginFilters);
        }

        return $imageFilters;
    }

    /**
     * Returns all image filters.
     *
     * @return array
     */
    public function getAllFilters()
    {
        $filterTypes = $this->getAllFilterTypes();
        $filters = [];

        foreach ($filterTypes as $filterType) {
            $filters[] = ComponentHelper::createComponent(['type' => $filterType], static::IMAGE_FILTER_INTERFACE);
        }

        return $filters;
    }

    /**
     * Apply an image filter and store the resulting image.
     *
     * @param ImageFilterInterface $filter the filter to use
     * @param string $imagePath the location of image
     * @param array $options filter options
     *
     * @return bool
     */
    public function applyFilterAndStore(ImageFilterInterface $filter, $imagePath, $options = []) {
        if ($filter->canApplyFilter()) {
            return $filter->applyAndStore($imagePath, $options, $imagePath);
        }

        return false;
    }

    /**
     * Apply an image filter and store the resulting blob.
     *
     * @param ImageFilterInterface $filter the filter to use
     * @param string $imagePath the location of image
     * @param array $options filter options
     *
     * @return false|string
     */

    public function applyFilterAndReturnBlob(ImageFilterInterface $filter, $imagePath, $options = []) {
        if ($filter->canApplyFilter()) {
            return $filter->applyAndReturnBlob($imagePath, $options);
        }

        return false;
    }


}
