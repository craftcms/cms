<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\PluginInterface;

/**
 * PluginEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class PluginEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var PluginInterface The plugin associated with this event
     */
    public $plugin;
}
