<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\AssetPreviewHandlerInterface;
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
    /**
     * @var Asset The asset associated with the event.
     */
    public Asset $asset;

    /**
     * An AssetPreview handler
     *
     * @var AssetPreviewHandlerInterface|null $previewHandler
     */
    public ?AssetPreviewHandlerInterface $previewHandler = null;
}
