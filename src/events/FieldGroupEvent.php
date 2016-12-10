<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use craft\models\FieldGroup;

/**
 * FieldGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldGroupEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var FieldGroup The field group associated with this event.
     */
    public $group;

    /**
     * @var boolean Whether the field group is brand new
     */
    public $isNew = false;
}
