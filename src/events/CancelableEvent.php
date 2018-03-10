<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * CancelableEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class CancelableEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var bool Whether to continue performing the action that called this event
     */
    public $isValid = true;
}
