<?php
namespace craft\services;

use Craft;
use craft\base\Component;
use craft\base\ImageFilterInterface;
use craft\helpers\Component as ComponentHelper;
use craft\image\filters\Brightness;
use craft\image\filters\Grayscale;
use craft\image\filters\Sepia;

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
    // Public Methods
    // =========================================================================

    /**
     * Returns the display name of this class.
     *
     * @return string
     */
    public function __toString()
    {
        return static::displayName();
    }

    /**
     * Returns all available image filter types.
     *
     * @return array the available volume type classes
     */
    public function getAllFilterTypes(): array
    {
        // TODO filters
        return [];

        $imageFilters = [
            Sepia::className(),
            Grayscale::className(),
            Brightness::className()
        ];

        // TODO filters
        foreach (Craft::$app->getPlugins()->call('getImageFilters', [], true) as $pluginFilters) {
            $imageFilters = array_merge($imageFilters, $pluginFilters);
        }

        return $imageFilters;
    }

    /**
     * Returns all image filters.
     *
     * @return ImageFilterInterface[]
     */
    public function getAllFilters(): array
    {
        $filterTypes = $this->getAllFilterTypes();
        $filters = [];

        foreach ($filterTypes as $filterType) {
            $filters[] = $this->getFilter($filterType);
        }

        return $filters;
    }

    /**
     * Return an instantiated image filter buy its component type.
     *
     * @param string $filterType
     *
     * @return ImageFilterInterface
     */
    public function getFilter(string $filterType): ImageFilterInterface
    {
        /** @var ImageFilterInterface $filter */
        $filter = ComponentHelper::createComponent(['type' => $filterType], ImageFilterInterface::class);

        return $filter;
    }

    /**
     * Apply an image filter and store the resulting image.
     *
     * @param ImageFilterInterface $filter    the filter to use
     * @param string               $imagePath the location of image
     * @param array                $options   filter options
     *
     * @return bool
     */
    public function applyFilterAndStore(ImageFilterInterface $filter, string $imagePath, array $options = []): bool
    {
        if ($filter->canApplyFilter()) {
            return $filter->applyAndStore($imagePath, $options, $imagePath);
        }

        return false;
    }

    /**
     * Apply an image filter and store the resulting blob.
     *
     * @param ImageFilterInterface $filter    the filter to use
     * @param string               $imagePath the location of image
     * @param array                $options   filter options
     *
     * @return false|string
     */
    public function applyFilterAndReturnBlob(ImageFilterInterface $filter, string $imagePath, array $options = [])
    {
        if ($filter->canApplyFilter()) {
            return $filter->applyAndReturnBlob($imagePath, $options);
        }

        return false;
    }
}
