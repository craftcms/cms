<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use CraftCms\Cms\Asset\Elements\Asset;

/**
 * Asset event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0
 */
class AssetEvent extends CancelableEvent
{
    /**
     * @var Asset The asset associated with the event.
     */
    public Asset $asset;

    /**
     * @var bool Whether the asset is brand new
     */
    public bool $isNew = false;
}
