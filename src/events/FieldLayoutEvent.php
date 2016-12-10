<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\models\FieldLayout;
use yii\base\Event;

/**
 * Field layout Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldLayoutEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var FieldLayout The field layout associated with this event.
     */
    public $layout;

    /**
     * @var boolean Whether the field is brand new
     */
    public $isNew = false;
}
