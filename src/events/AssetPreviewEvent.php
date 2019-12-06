<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 * @since 3.4.0
 */

namespace craft\events;

use craft\base\AssetPreviewInterface;
use craft\elements\Asset;
use yii\base\Event;

/**
 * Asset preview event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.4.0
 */
class AssetPreviewEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var Asset|null The asset Element associated with the event.
     */
    public $asset;

    /**
     * An AssetPreview handler
     *
     * @var AssetPreviewInterface $previewHandler
     */
    public $previewHandler;
}
