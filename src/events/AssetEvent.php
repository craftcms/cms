<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\elements\Asset;

/**
 * Asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Asset The asset model associated with the event.
     */
    public $asset;

    /**
     * @var boolean Whether the asset is brand new
     */
    public $isNew = false;
}
