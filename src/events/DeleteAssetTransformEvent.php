<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\AssetTransform;

/**
 * Delete asset transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteAssetTransformEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var AssetTransform The asset transform model associated with the event.
     */
    public $assetTransform;
}
