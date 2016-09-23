<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
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
