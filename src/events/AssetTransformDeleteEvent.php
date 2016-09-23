<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\AssetTransform;

/**
 * Asset transform delete event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class AssetTransformDeleteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var AssetTransform The asset transform model associated with the event.
     */
    public $assetTransform;
}
