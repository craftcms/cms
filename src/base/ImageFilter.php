<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

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
     * @inheritdoc
     */
    public function canApplyFilter(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function getFieldHtml(): string
    {
        return '';
    }
}
