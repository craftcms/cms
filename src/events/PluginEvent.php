<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\PluginInterface;
use yii\base\Event;

/**
 * PluginEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class PluginEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var PluginInterface|null The plugin associated with this event
     */
    public $plugin;
}
