<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\elements\Asset;
use yii\base\Event;

/**
 * Asset transform event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class DeleteAssetTransformsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Asset The asset associated with the event.
     */
    public $asset;
}
