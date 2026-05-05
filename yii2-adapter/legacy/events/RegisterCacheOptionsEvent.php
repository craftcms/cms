<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterCacheOptionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Events\ClearCachesOptionsResolving]] or [[\CraftCms\Cms\Utility\Events\ClearCachesTagOptionsResolving]] should be used instead.
 */
class RegisterCacheOptionsEvent extends Event
{
    /**
     * @var array List of registered cache options for the Clear Caches tool.
     */
    public array $options = [];
}
