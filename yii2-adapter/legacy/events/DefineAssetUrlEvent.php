<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use craft\models\ImageTransform;
use CraftCms\Cms\Asset\Elements\Asset;

/**
 * Define asset URL event class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Asset\Events\BeforeDefineAssetUrl} or {@see \CraftCms\Cms\Asset\Events\DefineAssetUrl} instead.
 */
class DefineAssetUrlEvent extends DefineUrlEvent
{
    /**
     * @var ImageTransform|string|array|null Asset transform index that is being generated (if any)
     * @since 4.0.0
     */
    public mixed $transform = null;

    /**
     * @var Asset The asset that is being transformed.
     * @since 4.0.0
     * @deprecated in 4.3.0. [[$sender]] should be used instead.
     */
    public Asset $asset;
}
