<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * CancelableEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class CancelableEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var boolean Whether to continue performing the action that called this event
     */
    public $isValid = true;
}
