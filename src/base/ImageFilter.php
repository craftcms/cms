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
abstract class ImageFilter extends Component implements ImageFilterInterface
{

    // Public Methods
    // =========================================================================

    /**
     * Returns true if the filter can be applied on the current Craft installation.
     *
     * @return boolean
     */
    public function canApplyFilter() {
        return true;
    }

    /**
     * Get the field Html for filter configuration.
     * 
     * @return string
     */
    public function getFieldHtml() {
        return '';
    }
}
