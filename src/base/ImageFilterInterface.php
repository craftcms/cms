<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\base;

/**
 * The base class for all image filters.  Any image filter must extend this class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
interface ImageFilterInterface extends ComponentInterface
{

    // Public Methods
    // =========================================================================

    /**
     * Apply the filter and return the resulting image blob
     *
     * @param string $imagePath the location of file to apply filter to.
     * @param array $options filter options, if any
     *
     * @return string|false
     */
    public function applyAndReturnBlob($imagePath, $options = []);

    /**
     * Apply the filter and store the image to a path. If path is omitted, store to original path.
     *
     * @param string $imagePath the location of file to apply filter to.
     * @param array $options filter options, if any
     * @param string $targetPath
     *
     * @return boolean
     */
    public function applyAndStore($imagePath, $options = [], $targetPath = '');

    /**
     * Returns true if the filter can be applied on the current Craft installation.
     *
     * @return boolean
     */
    public function canApplyFilter();

    /**
     * Get the field Html for filter configuration.
     * 
     * @return string
     */
    public function getFieldHtml();
}
