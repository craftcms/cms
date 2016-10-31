<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * SetAssetFilenameEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SetAssetFilenameEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The asset filename (sans extension).
     */
    public $filename;
}
