<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterRedactorPluginEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RegisterRedactorPluginEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string The Redactor plugin being registered
     */
    public $plugin;
}
