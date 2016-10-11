<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * ModelEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class ModelEvent extends \yii\base\ModelEvent
{
    // Properties
    // =========================================================================

    /**
     * @var boolean Whether to continue performing the action that called this event
     */
    public $isNew = false;
}
