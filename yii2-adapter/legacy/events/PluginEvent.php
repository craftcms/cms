<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;

/**
 * PluginEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use one of the events extending {@see \CraftCms\Cms\Plugin\Events\PluginEvent} instead.
 */
class PluginEvent extends Event
{
    /**
     * @var PluginInterface The plugin associated with this event
     */
    public PluginInterface $plugin;
}
