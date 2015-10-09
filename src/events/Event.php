<?php
/**
 * @link      http://buildwithcraft.com/
 * @copyright Copyright (c) 2015 Pixel & Tonic, Inc.
 * @license   http://buildwithcraft.com/license
 */

namespace craft\app\events;

/**
 * Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Event extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var boolean Whether to continue performing the action that called this event
     */
    public $isValid = true;
}
